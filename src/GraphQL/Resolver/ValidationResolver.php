<?php

namespace App\GraphQL\Resolver;

use App\Service\DialogValidationService;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

class ValidationResolver implements ResolverInterface, AliasedInterface
{
    public function __construct(
        private readonly DialogValidationService $validationService
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getAliases(): array
    {
        return [
            'validateDialogChoice' => 'validate_dialog_choice',
        ];
    }

    /**
     * @return array{valid: bool, message: string|null, nextNodeId: int|null}
     */
    public function validateDialogChoice(int $npcId, int $nodeId, int $choiceId): array
    {
        return $this->validationService->validateChoice($npcId, $nodeId, $choiceId);
    }
}
