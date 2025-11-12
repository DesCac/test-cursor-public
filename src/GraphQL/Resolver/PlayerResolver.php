<?php

namespace App\GraphQL\Resolver;

use App\Entity\Player;
use App\Entity\PlayerCharacter;
use App\Entity\Skill;
use App\Service\SkillAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;

class PlayerResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SkillAvailabilityService $skillAvailabilityService
    ) {
    }

    public function resolve(int $id): ?Player
    {
        return $this->entityManager->getRepository(Player::class)->find($id);
    }

    /**
     * @return array<int, Player>
     */
    public function resolveAll(): array
    {
        return $this->entityManager->getRepository(Player::class)->findAll();
    }

    /**
     * @return array<int, array{skill: Skill, canUnlock: bool, alreadyUnlocked: bool, reasons: list<string>}>
     */
    public function resolveAvailableSkills(PlayerCharacter $character): array
    {
        $skills = $this->entityManager->getRepository(Skill::class)->findAll();

        $results = [];
        foreach ($skills as $skill) {
            $evaluation = $this->skillAvailabilityService->evaluateUnlock($skill, $character);
            $results[] = [
                'skill' => $skill,
                'canUnlock' => $evaluation['canUnlock'],
                'alreadyUnlocked' => $evaluation['alreadyUnlocked'],
                'reasons' => $evaluation['reasons'],
            ];
        }

        usort(
            $results,
            static fn (array $left, array $right) => strcasecmp(
                $left['skill']->getName(),
                $right['skill']->getName()
            )
        );

        return $results;
    }
}

