<?php

namespace App\Controller\Api;

use App\Entity\Quest;
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
}
