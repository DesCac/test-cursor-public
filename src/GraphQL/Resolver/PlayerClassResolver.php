<?php

namespace App\GraphQL\Resolver;

use App\Entity\PlayerClass;
use Doctrine\ORM\EntityManagerInterface;

class PlayerClassResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolve(int $id): ?PlayerClass
    {
        return $this->entityManager->getRepository(PlayerClass::class)->find($id);
    }

    /**
     * @return array<int, PlayerClass>
     */
    public function resolveAll(): array
    {
        return $this->entityManager->getRepository(PlayerClass::class)->findBy([], ['name' => 'ASC']);
    }
}

