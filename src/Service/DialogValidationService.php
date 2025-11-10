<?php

namespace App\Service;

use App\Entity\DialogConnection;
use App\Entity\DialogNode;
use App\Entity\NPC;
use Doctrine\ORM\EntityManagerInterface;

class DialogValidationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Validates if a dialog choice is valid and returns the next node.
     *
     * @return array{valid: bool, message: string|null, nextNodeId: int|null}
     */
    public function validateChoice(int $npcId, int $nodeId, int $choiceId): array
    {
        $npc = $this->entityManager->getRepository(NPC::class)->find($npcId);
        if (!$npc) {
            return [
                'valid' => false,
                'message' => 'NPC not found',
                'nextNodeId' => null,
            ];
        }

        $node = $this->entityManager->getRepository(DialogNode::class)->find($nodeId);
        if (!$node || $node->getNpc()?->getId() !== $npcId) {
            return [
                'valid' => false,
                'message' => 'Dialog node not found or does not belong to this NPC',
                'nextNodeId' => null,
            ];
        }

        $connection = $this->entityManager->getRepository(DialogConnection::class)->find($choiceId);
        if (!$connection || $connection->getSourceNode()?->getId() !== $nodeId) {
            return [
                'valid' => false,
                'message' => 'Invalid choice for this dialog node',
                'nextNodeId' => null,
            ];
        }

        // Validate conditions if present
        $conditions = $connection->getConditions();
        if ($conditions !== null && !$this->evaluateConditions($conditions)) {
            return [
                'valid' => false,
                'message' => 'Conditions not met for this choice',
                'nextNodeId' => null,
            ];
        }

        return [
            'valid' => true,
            'message' => null,
            'nextNodeId' => $connection->getTargetNode()?->getId(),
        ];
    }

    /**
     * Evaluates conditions (placeholder for actual implementation).
     *
     * @param array<mixed> $conditions
     */
    private function evaluateConditions(array $conditions): bool
    {
        // This is a placeholder. In real implementation, you would evaluate
        // conditions based on player state, quest progress, inventory, etc.
        // For now, we'll just return true
        return true;
    }
}
