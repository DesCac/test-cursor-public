<?php

namespace App\Controller\Api;

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

        // This would handle updating the dialog graph structure
        // For now, it's a placeholder

        return $this->json(['success' => true]);
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
}
