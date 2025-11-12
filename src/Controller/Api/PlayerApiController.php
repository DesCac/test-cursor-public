<?php

namespace App\Controller\Api;

use App\Entity\CharacterClass;
use App\Entity\Player;
use App\Entity\PlayerCharacter;
use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/players')]
class PlayerApiController extends AbstractController
{
    #[Route('/{id}', name: 'api_player_get', methods: ['GET'])]
    public function get(int $id, EntityManagerInterface $em): JsonResponse
    {
        $player = $em->getRepository(Player::class)->find($id);

        if (!$player) {
            return $this->json(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->serializePlayer($player));
    }

    #[Route('/{id}/characters/{characterId}/unlock-skill', name: 'api_player_unlock_skill', methods: ['POST'])]
    public function unlockSkill(
        int $id,
        int $characterId,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $player = $em->getRepository(Player::class)->find($id);

        if (!$player) {
            return $this->json(['error' => 'Player not found'], Response::HTTP_NOT_FOUND);
        }

        $character = $em->getRepository(PlayerCharacter::class)->find($characterId);

        if (!$character || $character->getPlayer()?->getId() !== $player->getId()) {
            return $this->json(['error' => 'Character not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data) || !isset($data['skillId'])) {
            return $this->json(['error' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        $skillId = (int) $data['skillId'];
        $skill = $em->getRepository(Skill::class)->find($skillId);

        if (!$skill) {
            return $this->json(['error' => 'Skill not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if all parent skills are unlocked
        foreach ($skill->getParents() as $parentSkill) {
            if (!$character->getUnlockedSkills()->contains($parentSkill)) {
                return $this->json([
                    'error' => 'Parent skill not unlocked',
                    'requiredSkill' => $parentSkill->getName()
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        // Check unlock conditions
        $conditions = $skill->getUnlockConditions();
        if (is_array($conditions)) {
            if (isset($conditions['level']) && is_array($conditions['level'])) {
                $minLevel = $conditions['level']['min'] ?? 0;
                if ($character->getLevel() < $minLevel) {
                    return $this->json([
                        'error' => 'Level requirement not met',
                        'requiredLevel' => $minLevel,
                        'currentLevel' => $character->getLevel()
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            if (isset($conditions['class']) && is_array($conditions['class'])) {
                $requiredClassId = $conditions['class']['id'] ?? null;
                if ($requiredClassId && $character->getCharacterClass()?->getId() !== $requiredClassId) {
                    return $this->json(['error' => 'Class requirement not met'], Response::HTTP_BAD_REQUEST);
                }
            }

            if (isset($conditions['quests']) && is_array($conditions['quests'])) {
                $requiredQuestIds = $conditions['quests']['completed'] ?? [];
                $completedQuestIds = $character->getCompletedQuestIds() ?? [];
                foreach ($requiredQuestIds as $questId) {
                    if (!in_array($questId, $completedQuestIds, true)) {
                        return $this->json([
                            'error' => 'Quest requirement not met',
                            'requiredQuestId' => $questId
                        ], Response::HTTP_BAD_REQUEST);
                    }
                }
            }
        }

        $character->addUnlockedSkill($skill);
        $em->flush();

        return $this->json([
            'success' => true,
            'character' => $this->serializeCharacter($character)
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlayer(Player $player): array
    {
        $characters = [];
        foreach ($player->getCharacters() as $char) {
            $characters[] = $this->serializeCharacter($char);
        }

        return [
            'id' => $player->getId(),
            'tgUserId' => $player->getTgUserId(),
            'username' => $player->getUsername(),
            'characters' => $characters,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCharacter(PlayerCharacter $character): array
    {
        $skills = [];
        foreach ($character->getUnlockedSkills() as $skill) {
            $skills[] = [
                'id' => $skill->getId(),
                'name' => $skill->getName(),
            ];
        }

        return [
            'id' => $character->getId(),
            'name' => $character->getName(),
            'level' => $character->getLevel(),
            'class' => [
                'id' => $character->getCharacterClass()?->getId(),
                'name' => $character->getCharacterClass()?->getName(),
            ],
            'unlockedSkills' => $skills,
            'completedQuestIds' => $character->getCompletedQuestIds(),
        ];
    }
}
