<?php

namespace App\GraphQL\Resolver;

use App\Entity\CharacterClass;
use App\Repository\CharacterClassRepository;
use Doctrine\ORM\EntityManagerInterface;

class CharacterClassResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CharacterClassRepository $classRepository
    ) {
    }

    public function resolve(int $id): ?CharacterClass
    {
        return $this->entityManager->getRepository(CharacterClass::class)->find($id);
    }

    /**
     * @return array<CharacterClass>
     */
    public function resolveAll(): array
    {
        return $this->entityManager->getRepository(CharacterClass::class)->findAll();
    }

    /**
     * @return array<CharacterClass>
     */
    public function resolveRootClasses(): array
    {
        return $this->classRepository->findRootClasses();
    }
}
