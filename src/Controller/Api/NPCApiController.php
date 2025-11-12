<?php

namespace App\Controller\Api;

use App\Entity\DialogConnection;
use App\Entity\DialogNode;
use App\Entity\NPC;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/npcs')]
class NPCApiController extends AbstractController
{
    #[Route('/{id}', name: 'api_npc_get', methods: ['GET'])]
    public function get(int $id, EntityManagerInterface $em): JsonResponse
    {
        $npc = $em->getRepository(NPC::class)->find($id);

        if (!$npc) {
            return $this->json(['error' => 'NPC not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeNPC($npc));
    }

    #[Route('/{id}', name: 'api_npc_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $npc = $em->getRepository(NPC::class)->find($id);

        if (!$npc) {
            return $this->json(['error' => 'NPC not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON in request body'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $npc->setName($data['name']);
        }

        if (isset($data['description'])) {
            $npc->setDescription($data['description']);
        }

        $em->flush();

        return $this->json($this->serializeNPC($npc));
    }

    #[Route('/{id}/nodes', name: 'api_npc_update_nodes', methods: ['PUT'])]
    public function updateNodes(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $npc = $em->getRepository(NPC::class)->find($id);

        if (!$npc) {
            return $this->json(['error' => 'NPC not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON in request body'], Response::HTTP_BAD_REQUEST);
        }

        $nodesPayload = $data['nodes'] ?? [];
        $connectionsPayload = $data['connections'] ?? [];

        if (!is_array($nodesPayload)) {
            return $this->json(['error' => 'Field "nodes" must be an array'], Response::HTTP_BAD_REQUEST);
        }

        $existingNodes = [];
        foreach ($npc->getDialogNodes() as $nodeEntity) {
            if ($nodeEntity->getId() !== null) {
                $existingNodes[$nodeEntity->getId()] = $nodeEntity;
            }
        }

        $clientNodeMap = [];
        $processedNodes = [];

        foreach ($nodesPayload as $nodePayload) {
            if (!is_array($nodePayload)) {
                continue;
            }

            $nodeId = isset($nodePayload['id']) ? (int) $nodePayload['id'] : null;
            $clientId = (string) ($nodePayload['clientId'] ?? ($nodeId ?? uniqid('node_', true)));

            if ($nodeId !== null && isset($existingNodes[$nodeId])) {
                $dialogNode = $existingNodes[$nodeId];
            } else {
                $dialogNode = new DialogNode();
                $dialogNode->setNpc($npc);
                $em->persist($dialogNode);
            }

            $dialogNode->setNodeType((string) ($nodePayload['type'] ?? 'dialog'));
            $dialogNode->setText($nodePayload['text'] ?? null);
            $dialogNode->setConditions($this->normalizeArray($nodePayload['conditions'] ?? null));
            $dialogNode->setPositionX(isset($nodePayload['positionX']) ? (float) $nodePayload['positionX'] : null);
            $dialogNode->setPositionY(isset($nodePayload['positionY']) ? (float) $nodePayload['positionY'] : null);

            $clientNodeMap[$clientId] = $dialogNode;

            if ($dialogNode->getId() !== null) {
                $clientNodeMap[(string) $dialogNode->getId()] = $dialogNode;
            }

            $processedNodes[] = $dialogNode;
        }

        foreach ($npc->getDialogNodes() as $dialogNode) {
            if (!in_array($dialogNode, $processedNodes, true)) {
                $npc->removeDialogNode($dialogNode);
                $em->remove($dialogNode);
            }
        }

        foreach ($npc->getDialogNodes() as $dialogNode) {
            foreach ($dialogNode->getOutgoingConnections() as $connection) {
                $em->remove($connection);
            }
        }

        $em->flush();

        // refresh client map with generated IDs
        foreach ($clientNodeMap as $clientId => $dialogNode) {
            if ($dialogNode->getId() !== null) {
                $clientNodeMap[(string) $dialogNode->getId()] = $dialogNode;
            }
        }

        if (is_array($connectionsPayload)) {
            foreach ($connectionsPayload as $connectionPayload) {
                if (!is_array($connectionPayload)) {
                    continue;
                }

                $sourceKey = (string) ($connectionPayload['sourceId'] ?? '');
                $targetKey = (string) ($connectionPayload['targetId'] ?? '');

                if ($sourceKey === '' || $targetKey === '') {
                    continue;
                }

                if (!isset($clientNodeMap[$sourceKey], $clientNodeMap[$targetKey])) {
                    continue;
                }

                /** @var DialogNode $sourceNode */
                $sourceNode = $clientNodeMap[$sourceKey];
                /** @var DialogNode $targetNode */
                $targetNode = $clientNodeMap[$targetKey];

                $connection = new DialogConnection();
                $connection->setSourceNode($sourceNode);
                $connection->setTargetNode($targetNode);
                $connection->setChoiceText($connectionPayload['choiceText'] ?? null);
                $connection->setConditions($this->normalizeArray($connectionPayload['conditions'] ?? null));

                $em->persist($connection);
            }
        }

        $em->flush();

        return $this->json($this->serializeNPC($npc));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNPC(NPC $npc): array
    {
        $nodes = [];
        foreach ($npc->getDialogNodes() as $node) {
            $nodes[] = $this->serializeNode($node);
        }

        return [
            'id' => $npc->getId(),
            'name' => $npc->getName(),
            'description' => $npc->getDescription(),
            'nodes' => $nodes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNode(DialogNode $node): array
    {
        $connections = [];
        foreach ($node->getOutgoingConnections() as $conn) {
            $connections[] = [
                'id' => $conn->getId(),
                'targetNodeId' => $conn->getTargetNode()?->getId(),
                'choiceText' => $conn->getChoiceText(),
                'conditions' => $conn->getConditions(),
            ];
        }

        return [
            'id' => $node->getId(),
            'type' => $node->getNodeType(),
            'text' => $node->getText(),
            'conditions' => $node->getConditions(),
            'positionX' => $node->getPositionX(),
            'positionY' => $node->getPositionY(),
            'connections' => $connections,
        ];
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>|null
     */
    private function normalizeArray(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (!is_array($value)) {
            return null;
        }

        if ($value === []) {
            return null;
        }

        return $value;
    }
}
