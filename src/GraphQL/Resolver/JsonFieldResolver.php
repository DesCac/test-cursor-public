<?php

namespace App\GraphQL\Resolver;

class JsonFieldResolver
{
    /**
     * Сериализует массив в JSON строку
     * 
     * @param array|null $data
     * @return string|null
     */
    public function resolve(?array $data): ?string
    {
        if ($data === null) {
            return null;
        }
        
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
