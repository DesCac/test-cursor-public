<?php

namespace App\GraphQL\Resolver;

use App\Entity\Quest;
use Doctrine\ORM\EntityManagerInterface;

class QuestResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolve(int $id): ?Quest
    {
        return $this->entityManager->getRepository(Quest::class)->find($id);
    }

    /**
     * @return array<Quest>
     */
    public function resolveAll(): array
    {
        return $this->entityManager->getRepository(Quest::class)->findAll();
    }
}
