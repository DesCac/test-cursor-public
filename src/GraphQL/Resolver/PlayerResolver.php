<?php

namespace App\GraphQL\Resolver;

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;

class PlayerResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolve(int $id): ?Player
    {
        return $this->entityManager->getRepository(Player::class)->find($id);
    }

    public function resolveByTgUserId(string $tgUserId): ?Player
    {
        return $this->entityManager->getRepository(Player::class)->findOneBy(['tgUserId' => $tgUserId]);
    }

    /**
     * @return array<Player>
     */
    public function resolveAll(): array
    {
        return $this->entityManager->getRepository(Player::class)->findAll();
    }
}
