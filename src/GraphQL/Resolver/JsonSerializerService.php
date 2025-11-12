<?php

namespace App\GraphQL\Resolver;

class JsonSerializerService
{
    public function serialize(?array $data): ?string
    {
        return $data !== null ? json_encode($data, JSON_THROW_ON_ERROR) : null;
    }
}

