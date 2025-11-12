# Исправления GraphQL API

## Проблемы, которые были обнаружены

### 1. Ошибка: "Cannot query field \"type\" on type \"DialogNode\""

**Причина:** В GraphQL схеме поле называется `nodeType`, а не `type`.

**Исправление:** В Entity классе используется свойство `$nodeType` с методом `getNodeType()`, и в GraphQL схеме оно корректно названо `nodeType`.

### 2. Ошибка: "Cannot query field \"questNodes\" on type \"Quest\""

**Причина:** В GraphQL схеме поле называется `logicNodes`, а не `questNodes`.

**Исправление:** В Entity `Quest` используется свойство `$logicNodes` с методом `getLogicNodes()`, и в GraphQL схеме оно корректно названо `logicNodes`.

### 3. Проблема с маппингом поля `connections`

**Причина:** В GraphQL схеме поле называется `connections`, но в Entity классах методы называются `getOutgoingConnections()` и `getIncomingConnections()`.

**Исправление:** Добавлены явные резолверы для поля `connections`:
- `DialogNode.connections` → `@=value.getOutgoingConnections()`
- `QuestNode.connections` → `@=value.getOutgoingConnections()`

### 4. Проблема с JSON полями

**Причина:** Поля `conditions`, `data`, `objectives`, `rewards`, `requirements` в Entity классах хранятся как массивы PHP (тип `array`), но GraphQL возвращает их как `String`.

**Исправление:** Добавлены резолверы с `json_encode()` для всех JSON полей:

```yaml
conditions:
    type: "String"
    resolve: "@=value.getConditions() ? json_encode(value.getConditions()) : null"
```

## Изменённые типы в GraphQL схеме

### DialogNode
- `conditions`: добавлен резолвер для сериализации JSON
- `connections`: добавлен резолвер для маппинга на `getOutgoingConnections()`

### DialogConnection
- `conditions`: добавлен резолвер для сериализации JSON

### QuestNode
- `data`: добавлен резолвер для сериализации JSON
- `conditions`: добавлен резолвер для сериализации JSON
- `connections`: добавлен резолвер для маппинга на `getOutgoingConnections()`

### QuestConnection
- `conditions`: добавлен резолвер для сериализации JSON

### Quest
- `objectives`: добавлен резолвер для сериализации JSON
- `rewards`: добавлен резолвер для сериализации JSON
- `requirements`: добавлен резолвер для сериализации JSON

## Правильные имена полей для запросов

### DialogNode
- ✅ `nodeType` (не `type`)
- ✅ `text`
- ✅ `conditions` (возвращает JSON строку)
- ✅ `positionX`
- ✅ `positionY`
- ✅ `connections` (возвращает исходящие соединения)

### QuestNode
- ✅ `nodeType` (не `type`)
- ✅ `data` (возвращает JSON строку)
- ✅ `conditions` (возвращает JSON строку)
- ✅ `positionX`
- ✅ `positionY`
- ✅ `connections` (возвращает исходящие соединения)

### Quest
- ✅ `logicNodes` (не `questNodes`)
- ✅ `objectives` (возвращает JSON строку)
- ✅ `rewards` (возвращает JSON строку)
- ✅ `requirements` (возвращает JSON строку)

## Как использовать JSON поля

JSON поля возвращаются как строки. Для парсинга во фронтенде используйте:

```javascript
const quest = data.quest;
const objectives = JSON.parse(quest.objectives);
const rewards = JSON.parse(quest.rewards);
```

## Следующие шаги

1. **Очистить кеш Symfony:**
   ```bash
   docker-compose exec php bin/console cache:clear
   ```

2. **Проверить GraphQL схему:**
   Откройте `/graphql` или `/graphiql.html` и используйте примеры запросов из `GRAPHQL_EXAMPLES.md`

3. **Обновить фронтенд код:**
   Убедитесь, что все GraphQL запросы используют правильные имена полей:
   - `nodeType` вместо `type`
   - `logicNodes` вместо `questNodes`
