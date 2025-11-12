<?php

namespace App\GraphQL\Resolver;

use App\Entity\PlayerCharacter;
use App\Entity\Skill;
use App\Service\SkillAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;

class SkillAvailabilityResolver
{
    public function __construct(
        private readonly SkillAvailabilityService $availabilityService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<int, array{skill: Skill, canUnlock: bool, blockingReasons: array<int, string>}>
     */
    public function resolveForCharacter(PlayerCharacter $character): array
    {
        $skills = $this->entityManager->getRepository(Skill::class)->findBy([], ['name' => 'ASC']);
        $results = [];

        foreach ($skills as $skill) {
            $blockingReasons = $this->availabilityService->getBlockingReasons($character, $skill);
            $results[] = [
                'skill' => $skill,
                'canUnlock' => $blockingReasons === [],
                'blockingReasons' => $blockingReasons,
            ];
        }

        return $results;
    }
}

