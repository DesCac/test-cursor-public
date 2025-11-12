<?php

namespace App\GraphQL\Resolver;

use App\Entity\Skill;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;

class SkillResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SkillRepository $skillRepository
    ) {
    }

    public function resolve(int $id): ?Skill
    {
        return $this->entityManager->getRepository(Skill::class)->find($id);
    }

    /**
     * @return array<Skill>
     */
    public function resolveAll(): array
    {
        return $this->skillRepository->findAllWithRelations();
    }
}
