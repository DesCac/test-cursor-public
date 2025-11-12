<?php

namespace App\Repository;

use App\Entity\CharacterClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CharacterClass>
 */
class CharacterClassRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CharacterClass::class);
    }

    /**
     * @return CharacterClass[]
     */
    public function findRootClasses(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CharacterClass[]
     */
    public function findByParentId(int $parentId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :parentId')
            ->setParameter('parentId', $parentId)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
