<?php

namespace App\GraphQL\Resolver;

use App\Entity\Skill;
use Doctrine\ORM\EntityManagerInterface;

class SkillResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolve(int $id): ?Skill
    {
        return $this->entityManager->getRepository(Skill::class)->find($id);
    }

    /**
     * @return array<int, Skill>
     */
    public function resolveAll(): array
    {
        return $this->entityManager->getRepository(Skill::class)->findAll();
    }
}

