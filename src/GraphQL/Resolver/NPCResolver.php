<?php

namespace App\GraphQL\Resolver;

use App\Entity\NPC;
use Doctrine\ORM\EntityManagerInterface;

class NPCResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolve(int $id): ?NPC
    {
        return $this->entityManager->getRepository(NPC::class)->find($id);
    }

    /**
     * @return array<NPC>
     */
    public function resolveAll(): array
    {
        return $this->entityManager->getRepository(NPC::class)->findAll();
    }
}
