# 🎭 Пример: Диалог со старейшиной (Elder Sage)

## Визуальная схема графа диалога

```
┌──────────────────────────────────────────────────────────────┐
│  START (id: 94)                                               │
│  "Приветствую тебя, путник. Я чувствую в тебе силу..."      │
└────────────────────┬─────────────────────────────────────────┘
                     │
                     ↓ (автопереход)
┌──────────────────────────────────────────────────────────────┐
│  DIALOG (id: 95)                                              │
│  "Ты пришел в нужное время. Наш город в опасности..."        │
└──────────┬───────────────────────────────┬───────────────────┘
           │                               │
  [Расскажи подробнее]          [Меня это не интересует]
           │                               │
           ↓                               ↓
┌──────────────────────┐      ┌───────────────────────────────┐
│ DIALOG (id: 96)      │      │ DIALOG (id: 97)               │
│ "Тёмные силы..."     │      │ "Понимаю твою                 │
│                      │      │  осторожность. Но             │
└──────┬───────────────┘      │  подумай - награда            │
       │                      │  будет щедрой..."             │
       ↓                      └──────┬─────────────┬──────────┘
┌──────────────────────┐            │             │
│ CONDITION (id: 98)   │    [Какая награда?]  [Нет, спасибо]
│ Проверка уровня      │            │             │
│ героя                │            ↓             ↓
└───┬────────┬─────────┘   ┌──────────────┐  ┌─────────────┐
    │        │             │ DIALOG (110) │  │ END (108)   │
    │        │             │ "Я предлагаю │  │ "Возможно..." │
    │        │             │  5000 золота"│  └─────────────┘
level<5   level>=5         └──────┬───────┘
    │        │                    │
    ↓        ↓            [Ну, раз так...]
┌────────┐ ┌──────────────────┐   │
│DIALOG  │ │ DIALOG (id: 100) │←──┘
│(id:99) │ │ "Отлично! Твой   │
│"Увы,   │ │  уровень подходит│
│ты      │ │  Вот что тебе    │
│слишком │ │  нужно сделать..." │
│слаб"   │ └────────┬─────────┘
└───┬────┘          │
    │          ┌────┴────────────────────┐
    ↓          │                         │
┌─────────┐  [Я принимаю]    [Мне нужно подумать]
│END(109) │    │                         │
│"Иди,    │    ↓                         ↓
│тренируй-│ ┌──────────────┐    ┌───────────────┐
│ся"      │ │ ACTION (101) │    │ DIALOG (102)  │
└─────────┘ │ "Да пребудет │    │ "Жаль... Но  │
            │  с тобой сила│    │  если         │
            │  Вот карта..." │    │  передумаешь" │
            └──────┬───────┘    └───────┬───────┘
                   │                    │
                   ↓                    ↓
            ┌──────────────┐    ┌──────────────┐
            │CONDITION(103)│    │  END (108)   │
            │Проверка      │    │ "Возможно,   │
            │ключа         │    │  когда-      │
            └──┬───────┬───┘    │  нибудь..."  │
               │       │        └──────────────┘
         [Есть] │       │ [Нет ключа]
               │       │
               ↓       ↓
        ┌─────────┐ ┌────────────┐
        │DIALOG   │ │ACTION(106) │
        │(104)    │ │"Ты         │
        │"О! У    │ │получаешь   │
        │тебя есть│ │1000 золота"│
        │ключ!"   │ └──────┬─────┘
        └────┬────┘        │
             │             │
             ↓             │
        ┌─────────┐        │
        │ACTION   │        │
        │(105)    │        │
        │"Ты      │        │
        │получаешь│        │
        │легенд.  │        │
        │оружие!" │        │
        └────┬────┘        │
             │             │
             └──────┬──────┘
                    ↓
            ┌──────────────┐
            │  END (107)   │
            │ "Спасибо,    │
            │  герой!"     │
            └──────────────┘
```

---

## 🎮 Как игрок перемещается по этому графу

### Сценарий 1: Игрок высокого уровня принимает квест

```
1. START (94) → автопереход
2. DIALOG (95) → игрок выбирает "Расскажи подробнее"
3. DIALOG (96) → автопереход
4. CONDITION (98) → проверка level >= 5 → УСПЕХ
5. DIALOG (100) → игрок выбирает "Я принимаю этот квест!"
6. ACTION (101) → получение карты и амулета
7. CONDITION (103) → проверка наличия ключа
   - Если ЕСТЬ ключ → DIALOG (104) → ACTION (105) → END (107)
   - Если НЕТ ключа → ACTION (106) → END (107)
```

### Сценарий 2: Игрок низкого уровня

```
1. START (94) → автопереход
2. DIALOG (95) → игрок выбирает "Расскажи подробнее"
3. DIALOG (96) → автопереход
4. CONDITION (98) → проверка level >= 5 → ПРОВАЛ
5. DIALOG (99) "Увы, ты ещё слишком слаб"
6. END (109) "Иди, тренируйся и возвращайся"
```

### Сценарий 3: Игрок отказывается от квеста

```
1. START (94) → автопереход
2. DIALOG (95) → игрок выбирает "Меня это не интересует"
3. DIALOG (97) → игрок выбирает "Нет, спасибо"
4. END (108) "Возможно, когда-нибудь ты вернёшься"
```

### Сценарий 4: Игрок спрашивает о награде

```
1. START (94) → автопереход
2. DIALOG (95) → игрок выбирает "Меня это не интересует"
3. DIALOG (97) → игрок выбирает "Какая награда?"
4. DIALOG (110) "Я предлагаю 5000 золотых монет..."
5. DIALOG (111) → возврат к CONDITION (98)
6. Далее как в сценарии 1
```

---

## 📊 Структура данных для навигации

### GraphQL запрос для получения всего графа:

```graphql
{
  npc(id: 9) {
    id
    name
    dialogNodes {
      id
      nodeType
      text
      conditions
      
      connections {
        id
        choiceText
        conditions
        
        targetNode {
          id
          nodeType
        }
      }
    }
  }
}
```

### Пример ответа для узла с выбором:

```json
{
  "id": 95,
  "nodeType": "dialog",
  "text": "Ты пришел в нужное время. Наш город в опасности...",
  "conditions": null,
  "connections": [
    {
      "id": 285,
      "choiceText": "Расскажи подробнее об опасности",
      "conditions": null,
      "targetNode": {
        "id": 96,
        "nodeType": "dialog"
      }
    },
    {
      "id": 286,
      "choiceText": "Меня это не интересует",
      "conditions": null,
      "targetNode": {
        "id": 97,
        "nodeType": "dialog"
      }
    }
  ]
}
```

### Пример узла с условием:

```json
{
  "id": 98,
  "nodeType": "condition",
  "text": "Проверка уровня героя",
  "conditions": "{\"level\":{\"min\":5}}",
  "connections": [
    {
      "id": 269,
      "choiceText": "Уровень < 5",
      "conditions": "{\"level\":{\"max\":4}}",
      "targetNode": {
        "id": 99,
        "nodeType": "dialog"
      }
    },
    {
      "id": 270,
      "choiceText": "Уровень >= 5",
      "conditions": "{\"level\":{\"min\":5}}",
      "targetNode": {
        "id": 100,
        "nodeType": "dialog"
      }
    }
  ]
}
```

---

## 💻 Код навигации в игровом клиенте

### JavaScript пример:

```javascript
class DialogNavigator {
  constructor(npc) {
    this.npc = npc;
    this.nodesMap = {};
    
    // Создаём хеш-таблицу для быстрого поиска узлов
    npc.dialogNodes.forEach(node => {
      this.nodesMap[node.id] = node;
    });
    
    // Находим стартовый узел
    this.currentNode = npc.dialogNodes.find(n => n.nodeType === 'start');
  }
  
  // Получить текущий узел
  getCurrentNode() {
    return this.currentNode;
  }
  
  // Получить доступные варианты выбора
  getChoices(playerState) {
    return this.currentNode.connections.filter(conn => {
      // Проверяем условия для каждой связи
      if (conn.conditions) {
        const conditions = JSON.parse(conn.conditions);
        return this.checkConditions(conditions, playerState);
      }
      return true;
    });
  }
  
  // Проверка условий
  checkConditions(conditions, playerState) {
    if (conditions.level) {
      if (conditions.level.min && playerState.level < conditions.level.min) {
        return false;
      }
      if (conditions.level.max && playerState.level > conditions.level.max) {
        return false;
      }
    }
    
    if (conditions.inventory && conditions.inventory.has) {
      if (!playerState.inventory.includes(conditions.inventory.has)) {
        return false;
      }
    }
    
    return true;
  }
  
  // Перейти к следующему узлу
  selectChoice(connectionId) {
    const connection = this.currentNode.connections.find(c => c.id === connectionId);
    if (!connection) {
      throw new Error('Invalid connection');
    }
    
    const nextNodeId = connection.targetNode.id;
    this.currentNode = this.nodesMap[nextNodeId];
    
    return this.currentNode;
  }
  
  // Автоматический переход (для узлов без выбора)
  autoAdvance(playerState) {
    if (this.currentNode.nodeType === 'condition') {
      // Находим первую связь с подходящими условиями
      const validConnection = this.currentNode.connections.find(conn => {
        if (!conn.conditions) return true;
        const conditions = JSON.parse(conn.conditions);
        return this.checkConditions(conditions, playerState);
      });
      
      if (validConnection) {
        return this.selectChoice(validConnection.id);
      }
    }
    
    // Если только одна связь без текста - автопереход
    if (this.currentNode.connections.length === 1 && 
        !this.currentNode.connections[0].choiceText) {
      return this.selectChoice(this.currentNode.connections[0].id);
    }
    
    return this.currentNode;
  }
  
  // Закончен ли диалог?
  isFinished() {
    return this.currentNode.nodeType === 'end';
  }
}

// Использование:
const playerState = {
  level: 6,
  inventory: ['ancient_key', 'sword']
};

const navigator = new DialogNavigator(npcData);

// Игровой цикл
while (!navigator.isFinished()) {
  const node = navigator.getCurrentNode();
  
  // Показываем текст
  console.log(node.text);
  
  // Автопереход если возможен
  navigator.autoAdvance(playerState);
  
  if (navigator.isFinished()) break;
  
  // Получаем доступные варианты
  const choices = navigator.getChoices(playerState);
  
  if (choices.length > 0) {
    // Показываем варианты игроку
    choices.forEach((choice, index) => {
      console.log(`${index + 1}. ${choice.choiceText}`);
    });
    
    // Игрок выбирает
    const selectedIndex = await getPlayerChoice();
    const selectedConnection = choices[selectedIndex];
    
    // Переходим к следующему узлу
    navigator.selectChoice(selectedConnection.id);
  }
}

console.log("Диалог завершён!");
```

---

## 🎯 Итоги

### Ключевые понятия:
1. **Узел (Node)** - точка в диалоге (реплика, условие, действие)
2. **Связь (Connection)** - переход от одного узла к другому
3. **Условие (Condition)** - проверка состояния игрока
4. **Навигация** - движение по связям от узла к узлу

### Логика работы:
1. Начинаем с `start` узла
2. Показываем текст текущего узла
3. Проверяем условия (если есть)
4. Показываем варианты выбора игроку
5. Переходим к следующему узлу по выбранной связи
6. Повторяем до узла `end`

**Теперь вы знаете, как работает система диалогов!** 🎮

