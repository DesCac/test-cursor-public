<?php

namespace App\Repository;

use App\Entity\Skill;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Skill>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    /**
     * @return Skill[]
     */
    public function findRootSkills(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.parents', 'p')
            ->having('COUNT(p.id) = 0')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Skill[]
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.parents', 'p')
            ->leftJoin('s.children', 'c')
            ->addSelect('p', 'c')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
