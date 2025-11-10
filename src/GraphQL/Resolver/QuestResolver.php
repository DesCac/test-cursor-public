<?php

namespace App\GraphQL\Resolver;

use App\Entity\Quest;
use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class QuestResolver implements ResolverInterface, AliasedInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getAliases(): array
    {
        return [
            'resolve' => 'quest',
            'resolveAll' => 'quests',
        ];
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
