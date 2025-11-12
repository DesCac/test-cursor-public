<?php

namespace App\Controller\Api;

use App\Entity\Quest;
use App\Entity\QuestConnection;
use App\Entity\QuestNode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/quests')]
class QuestApiController extends AbstractController
{
    #[Route('/{id}', name: 'api_quest_get', methods: ['GET'])]
    public function get(int $id, EntityManagerInterface $em): JsonResponse
    {
        $quest = $em->getRepository(Quest::class)->find($id);

        if (!$quest) {
            return $this->json(['error' => 'Quest not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializeQuest($quest));
    }

    #[Route('/{id}', name: 'api_quest_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $quest = $em->getRepository(Quest::class)->find($id);

        if (!$quest) {
            return $this->json(['error' => 'Quest not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'Invalid JSON in request body'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name'])) {
            $quest->setName($data['name']);
        }

        if (isset($data['description'])) {
            $quest->setDescription($data['description']);
        }

        $em->flush();

        return $this->json($this->serializeQuest($quest));
    }

    #[Route('/{id}/nodes', name: 'api_quest_update_nodes', methods: ['PUT'])]
    public function updateNodes(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $quest = $em->getRepository(Quest::class)->find($id);

        if (!$quest) {
            return $this->json(['error' => 'Quest not found'], Response::HTTP_NOT_FOUND);
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
        foreach ($quest->getLogicNodes() as $nodeEntity) {
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
            $clientId = (string) ($nodePayload['clientId'] ?? ($nodeId ?? uniqid('quest_node_', true)));

            if ($nodeId !== null && isset($existingNodes[$nodeId])) {
                $questNode = $existingNodes[$nodeId];
            } else {
                $questNode = new QuestNode();
                $questNode->setQuest($quest);
                $em->persist($questNode);
            }

            $questNode->setNodeType((string) ($nodePayload['type'] ?? 'objective'));
            $questNode->setData($this->normalizeArray($nodePayload['data'] ?? null));
            $questNode->setConditions($this->normalizeArray($nodePayload['conditions'] ?? null));
            $questNode->setPositionX(isset($nodePayload['positionX']) ? (float) $nodePayload['positionX'] : null);
            $questNode->setPositionY(isset($nodePayload['positionY']) ? (float) $nodePayload['positionY'] : null);

            $clientNodeMap[$clientId] = $questNode;

            if ($questNode->getId() !== null) {
                $clientNodeMap[(string) $questNode->getId()] = $questNode;
            }

            $processedNodes[] = $questNode;
        }

        foreach ($quest->getLogicNodes() as $questNode) {
            if (!in_array($questNode, $processedNodes, true)) {
                $quest->removeLogicNode($questNode);
                $em->remove($questNode);
            }
        }

        foreach ($quest->getLogicNodes() as $questNode) {
            foreach ($questNode->getOutgoingConnections() as $connection) {
                $em->remove($connection);
            }
        }

        $em->flush();

        foreach ($clientNodeMap as $clientId => $questNode) {
            if ($questNode->getId() !== null) {
                $clientNodeMap[(string) $questNode->getId()] = $questNode;
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

                /** @var QuestNode $sourceNode */
                $sourceNode = $clientNodeMap[$sourceKey];
                /** @var QuestNode $targetNode */
                $targetNode = $clientNodeMap[$targetKey];

                $connection = new QuestConnection();
                $connection->setSourceNode($sourceNode);
                $connection->setTargetNode($targetNode);
                $connection->setConditions($this->normalizeArray($connectionPayload['conditions'] ?? null));

                $em->persist($connection);
            }
        }

        $em->flush();

        return $this->json($this->serializeQuest($quest));
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeQuest(Quest $quest): array
    {
        $nodes = [];
        foreach ($quest->getLogicNodes() as $node) {
            $nodes[] = $this->serializeNode($node);
        }

        return [
            'id' => $quest->getId(),
            'name' => $quest->getName(),
            'description' => $quest->getDescription(),
            'objectives' => $quest->getObjectives(),
            'rewards' => $quest->getRewards(),
            'requirements' => $quest->getRequirements(),
            'nodes' => $nodes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeNode(QuestNode $node): array
    {
        $connections = [];
        foreach ($node->getOutgoingConnections() as $conn) {
            $connections[] = [
                'id' => $conn->getId(),
                'targetNodeId' => $conn->getTargetNode()?->getId(),
                'conditions' => $conn->getConditions(),
            ];
        }

        return [
            'id' => $node->getId(),
            'type' => $node->getNodeType(),
            'data' => $node->getData(),
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
