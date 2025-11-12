<?php

namespace App\GraphQL\Resolver;

use App\Entity\PlayerCharacter;
use Doctrine\ORM\EntityManagerInterface;

class PlayerCharacterResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolve(int $id): ?PlayerCharacter
    {
        return $this->entityManager->getRepository(PlayerCharacter::class)->find($id);
    }

    /**
     * @return array<int, PlayerCharacter>
     */
    public function resolveAll(): array
    {
        return $this->entityManager->getRepository(PlayerCharacter::class)->findBy([], ['name' => 'ASC']);
    }
}

