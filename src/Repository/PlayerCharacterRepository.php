<?php

namespace App\Repository;

use App\Entity\PlayerCharacter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlayerCharacter>
 */
class PlayerCharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerCharacter::class);
    }

    /**
     * @return PlayerCharacter[]
     */
    public function findByPlayerId(int $playerId): array
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.player = :playerId')
            ->setParameter('playerId', $playerId)
            ->orderBy('pc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
