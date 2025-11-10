<?php

namespace App\GraphQL\Resolver;

use App\Entity\NPC;
use App\Repository\NPCRepository;
use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class NPCResolver implements ResolverInterface, AliasedInterface
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
            'resolve' => 'npc',
            'resolveAll' => 'npcs',
        ];
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
