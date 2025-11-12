# Примеры GraphQL запросов

## Обзор исправлений

Были исправлены следующие проблемы в GraphQL схеме:

1. **Поле `connections`**: Теперь корректно маппится на `outgoingConnections()` из Entity
2. **JSON поля**: `conditions`, `data`, `objectives`, `rewards`, `requirements` теперь корректно сериализуются через специальный `JsonFieldResolver`
3. **Правильные имена полей**:
   - Используйте `nodeType` (не `type`) для DialogNode и QuestNode
   - Используйте `logicNodes` (не `questNodes`) для Quest

## Примеры запросов

### Получить NPC с диалоговыми узлами

```graphql
query {
  npc(id: 1) {
    id
    name
    description
    dialogNodes {
      id
      nodeType
      text
      conditions
      positionX
      positionY
      connections {
        id
        choiceText
        conditions
        targetNode {
          id
          nodeType
          text
        }
      }
    }
  }
}
```

### Получить список всех NPC

```graphql
query {
  npcs {
    id
    name
    description
  }
}
```

### Получить Quest с логическими узлами

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
      positionX
      positionY
      connections {
        id
        conditions
        targetNode {
          id
          nodeType
          data
        }
      }
    }
  }
}
```

### Получить список всех квестов

```graphql
query {
  quests {
    id
    name
    description
    objectives
    rewards
    requirements
  }
}
```

### Валидация выбора диалога

```graphql
query {
  validateDialogChoice(npcId: 1, nodeId: 2, choiceId: 3) {
    valid
    message
    nextNodeId
  }
}
```

## Важно

- **JSON поля** возвращаются как строки в формате JSON. Для их использования во фронтенде нужно выполнить `JSON.parse()`
- Поле **`connections`** возвращает только исходящие соединения (outgoing connections) из узла
- Имена полей чувствительны к регистру - используйте точные имена, указанные в примерах

## Обработка JSON полей во фронтенде

```javascript
// Пример обработки квеста
const quest = data.quest;

// Парсинг JSON полей
const objectives = quest.objectives ? JSON.parse(quest.objectives) : [];
const rewards = quest.rewards ? JSON.parse(quest.rewards) : {};
const requirements = quest.requirements ? JSON.parse(quest.requirements) : {};

console.log('Цели квеста:', objectives);
console.log('Награды:', rewards);
console.log('Требования:', requirements);
```

```javascript
// Пример обработки узла диалога
const dialogNode = data.npc.dialogNodes[0];

// Парсинг conditions
const conditions = dialogNode.conditions ? JSON.parse(dialogNode.conditions) : {};

console.log('Условия узла:', conditions);
```
