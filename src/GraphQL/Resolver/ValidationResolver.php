<?php

namespace App\GraphQL\Resolver;

use App\Service\DialogValidationService;

class ValidationResolver
{
    public function __construct(
        private readonly DialogValidationService $validationService
    ) {
    }

    /**
     * @return array{valid: bool, message: string|null, nextNodeId: int|null}
     */
    public function validateDialogChoice(int $npcId, int $nodeId, int $choiceId): array
    {
        return $this->validationService->validateChoice($npcId, $nodeId, $choiceId);
    }
}
