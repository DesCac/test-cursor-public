# 🎯 ФИНАЛЬНАЯ СБОРКА - CSS будет подключен!

## ✅ Последние критические исправления:

### 1. Исправлена проблема с `[object Object]` в атрибутах
**До:** Vue передавал объекты (position, dimensions, events) как строки в DOM
**После:** Добавлено `inheritAttrs: false` и явное определение всех props

В `LogicNode.vue`:
```javascript
defineOptions({
  inheritAttrs: false
});

const props = defineProps({
  id: String,
  type: String,
  data: Object,
  selected: Boolean,
  dragging: Boolean,
  resizing: Boolean,
  connectable: Boolean,
  position: Object,
  dimensions: Object,
  zIndex: [Number, String],
});
```

### 2. Добавлены явные `<link>` теги для CSS
**До:** CSS импортировался только в JS (не работало)
**После:** CSS подключается явно через `<link rel="stylesheet">`

В `templates/admin/npcs/edit.html.twig`:
```twig
{% block stylesheets %}
    {{ parent() }}
    <link rel="stylesheet" href="{{ asset('build/npc-editor.css') }}">
{% endblock %}
```

### 3. Настроен Vite для экстракции CSS
**vite.config.js:**
- Добавлено `cssCodeSplit: false` - весь CSS в одном файле на entry point
- Настроено `assetFileNames` для правильного именования CSS файлов
- CSS будет генерироваться как `npc-editor.css` и `quest-editor.css`

### 4. Порядок импортов (остался правильным)
```javascript
// 1. VueFlow стили
import '@vue-flow/core/dist/style.css';
import '@vue-flow/core/dist/theme-default.css';
import '@vue-flow/controls/dist/style.css';
import '@vue-flow/minimap/dist/style.css';

// 2. Наши стили (с !important)
import './styles/app.css';
```

## 📋 ФИНАЛЬНЫЕ КОМАНДЫ:

```bash
# 1. Пересобрать фронтенд (ОБЯЗАТЕЛЬНО!)
docker compose exec php npm run build

# 2. Проверить что CSS файлы создались
docker compose exec php ls -la public/build/*.css

# 3. Перезагрузить фикстуры (если ещё не делали)
docker compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

## 🌐 После пересборки:

1. **Жёсткая перезагрузка**: `Ctrl+Shift+R` (Mac: `Cmd+Shift+R`)
2. Откройте: `http://localhost:8080/admin/npcs/3/edit` (Elder Sage)
3. Откройте DevTools (F12) → вкладка Network
4. Проверьте что загрузился файл `npc-editor.css`

## ✨ Что вы увидите:

### ✅ Белые узлы с рамками разных цветов:
- 🟢 **Старт** - зелёная рамка (`border-color: rgba(16, 185, 129, 0.4)`)
- 🔵 **Диалог** - синяя рамка (`border-color: rgba(59, 130, 246, 0.4)`)
- 🟡 **Выбор** - жёлтая рамка (`border-color: rgba(251, 191, 36, 0.4)`)
- 🟣 **Действие** - фиолетовая рамка (`border-color: rgba(139, 92, 246, 0.4)`)
- 💖 **Условие** - розовая рамка (`border-color: rgba(236, 72, 153, 0.4)`)
- 🔴 **Завершение** - красная рамка (`border-color: rgba(239, 68, 68, 0.4)`)

### ✅ Полный интерфейс редактора:
- 📚 **Библиотека узлов** слева с иконками
- 🎨 **Canvas** в центре с узлами
- 🔗 **Фиолетовые линии** связей
- 🗺️ **MiniMap** справа внизу
- 🔍 **Controls** (zoom) слева вверху
- ⚙️ **Панель свойств** справа

### ✅ В DevTools вы увидите:
```javascript
// Console:
getComputedStyle(document.querySelector('.logic-node')).background
// Должен вернуть: "rgb(255, 255, 255)" ✅

getComputedStyle(document.querySelector('.logic-node')).borderColor
// Должен вернуть: "rgba(16, 185, 129, 0.4)" или другой цвет ✅

getComputedStyle(document.querySelector('.logic-node')).width
// Должен вернуть: "240px" ✅
```

## 🔍 Отладка если не работает:

### 1. Проверьте что CSS файл создался:
```bash
docker compose exec php ls -la public/build/npc-editor.css
```

### 2. Проверьте содержимое CSS:
```bash
docker compose exec php head -50 public/build/npc-editor.css
```

### 3. В браузере (Network tab):
- Найдите запрос `npc-editor.css`
- Проверьте Status Code: должен быть `200 OK`
- Проверьте Content-Type: должен быть `text/css`

### 4. В Elements tab:
- Найдите в `<head>` тег: `<link rel="stylesheet" href="/build/npc-editor.css">`
- Кликните на него - должен открыться CSS файл

## 🎨 Структура файлов после сборки:

```
public/build/
├── npc-editor.js          ✅ JavaScript
├── npc-editor.css         ✅ CSS (НОВЫЙ!)
├── quest-editor.js        ✅ JavaScript
├── quest-editor.css       ✅ CSS (НОВЫЙ!)
└── .vite/
    └── manifest.json      ✅ Манифест
```

## 🚨 ВАЖНО:

1. **ОБЯЗАТЕЛЬНО пересоберите**: `npm run build`
2. **ОБЯЗАТЕЛЬНО сделайте жёсткую перезагрузку**: `Ctrl+Shift+R`
3. Если не работает - проверьте что CSS файл создался и загружается в Network tab

---

**Теперь точно должно работать!** CSS файлы будут созданы отдельно и подключены через `<link>` теги! 🎉
