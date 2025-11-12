# Исправление GraphQL API - Финальная версия

## 🔴 Проблема

После первого исправления возникла новая ошибка:
```json
{
  "errors": [
    {
      "message": "Unexpected token '<', \"<!-- The f\"... is not valid JSON"
    }
  ]
}
```

## 🔍 Причина

Функция `json_encode()` недоступна в контексте Expression Language, используемом GraphQL Bundle. Попытка использовать её напрямую в YAML конфигурации приводила к ошибке, из-за которой сервер возвращал HTML страницу ошибки вместо JSON ответа.

## ✅ Решение

Создан специальный сервис `JsonFieldResolver` для сериализации JSON полей.

### 1. Создан новый резолвер
**Файл:** `src/GraphQL/Resolver/JsonFieldResolver.php`

```php
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
```

### 2. Обновлена конфигурация GraphQL

**Файл:** `config/graphql/types.yaml`

**Было (неправильно):**
```yaml
conditions:
    type: "String"
    resolve: "@=value.getConditions() ? json_encode(value.getConditions()) : null"
```

**Стало (правильно):**
```yaml
conditions:
    type: "String"
    resolve: "@=service('App\\\\GraphQL\\\\Resolver\\\\JsonFieldResolver').resolve(value.getConditions())"
```

## 📋 Список изменённых полей

Все JSON поля теперь используют `JsonFieldResolver`:

### Quest
- `objectives` → `JsonFieldResolver.resolve(value.getObjectives())`
- `rewards` → `JsonFieldResolver.resolve(value.getRewards())`
- `requirements` → `JsonFieldResolver.resolve(value.getRequirements())`

### DialogNode
- `conditions` → `JsonFieldResolver.resolve(value.getConditions())`

### DialogConnection
- `conditions` → `JsonFieldResolver.resolve(value.getConditions())`

### QuestNode
- `data` → `JsonFieldResolver.resolve(value.getData())`
- `conditions` → `JsonFieldResolver.resolve(value.getConditions())`

### QuestConnection
- `conditions` → `JsonFieldResolver.resolve(value.getConditions())`

## 🚀 Как применить исправления

### 1. Очистить кеш
```bash
docker-compose exec php bin/console cache:clear
```

Или если работаете локально без Docker:
```bash
php bin/console cache:clear
```

### 2. Проверить работу
Откройте GraphiQL: `http://localhost/graphiql.html`

Пример запроса:
```graphql
query {
  quest(id: 1) {
    id
    name
    description
    objectives
    rewards
    requirements
    logicNodes {
      id
      nodeType
      data
      conditions
      connections {
        id
        targetNode {
          id
          nodeType
        }
      }
    }
  }
}
```

## ⚠️ Важно

1. **Автоматическая регистрация сервиса:** `JsonFieldResolver` автоматически регистрируется в Symfony благодаря конфигурации в `services.yaml`:
   ```yaml
   App\GraphQL\Resolver\:
       resource: '../src/GraphQL/Resolver/'
   ```

2. **JSON поля возвращаются как строки:** Во фронтенде используйте `JSON.parse()` для парсинга:
   ```javascript
   const quest = data.quest;
   const objectives = JSON.parse(quest.objectives || '[]');
   const rewards = JSON.parse(quest.rewards || '{}');
   ```

3. **Обработка null значений:** Резолвер корректно обрабатывает `null` значения, возвращая `null` вместо строки `"null"`.

4. **Unicode символы:** Используются флаги `JSON_UNESCAPED_UNICODE` и `JSON_UNESCAPED_SLASHES` для правильного отображения русских символов и путей.

## 📚 Дополнительная информация

- GraphQL Bundle использует Symfony Expression Language для резолверов
- В Expression Language доступны только определённые функции и сервисы
- Для сложной логики лучше создавать отдельные сервисы-резолверы
- Все сервисы в `src/GraphQL/Resolver/` автоматически доступны для использования в GraphQL схеме
