# CI/CD Исправления - Полная история

## 🎯 Цель
Настроить GitHub Actions CI/CD для автоматического запуска тестов и PHPStan при каждом push.

## 📊 Статистика
- **Всего исправлено:** 13 критических багов
- **Улучшений от Bugbot:** 5
- **Измененных файлов:** 25+
- **Итераций отладки:** 8
- **Время на отладку:** ~1-2 часа

## 🐛 Критические баги (по порядку обнаружения)

### 1. Symfony Flex - symfony.lock конфликт
**Ошибка:** `Cannot access offset of type string on string`  
**Файл:** `symfony.lock`  
**Решение:**
- Удаление `symfony.lock` перед `composer install` в CI
- Установка с флагом `--no-scripts`
- Отдельный запуск `auto-scripts`

### 2. GraphQL - функция resolver не существует  
**Ошибка:** `The function "resolver" does not exist`  
**Файлы:** `config/graphql/types.yaml`, резолверы  
**Решение:**
- Изменил вызовы с `@=resolver('name')` на `@=service('App\\GraphQL\\Resolver\\...')`
- Убрал интерфейсы `ResolverInterface` и `AliasedInterface`
- Резолверы теперь простые сервисы

### 3. База данных - двойной суффикс _test
**Ошибка:** `database "rpg_quest_npc_test_test" does not exist`  
**Файл:** `config/packages/doctrine.yaml`  
**Решение:**
- Закомментировал `dbname_suffix: '_test'` в test окружении
- Прямое создание БД через psql в CI:
  ```bash
  PGPASSWORD=app_password psql -h localhost -U app -d postgres -c "CREATE DATABASE rpg_quest_npc_test;"
  ```

### 4. GraphQL - неправильная конфигурация builders
**Ошибка:** `Unrecognized option "resolver" under "overblog_graphql.definitions.builders.field.alias"`  
**Файл:** `config/packages/graphql.yaml`  
**Решение:** Удалена вся секция `builders` - она не нужна

### 5. PHPUnit - устаревший API
**Ошибка:** `Class "PHPUnit\TextUI\Command" not found`  
**Файл:** `bin/phpunit`  
**Решение:**
```php
// Было (PHPUnit <10):
PHPUnit\TextUI\Command::main();

// Стало (PHPUnit 10+):
$code = (new PHPUnit\TextUI\Application)->run($_SERVER['argv']);
exit($code);
```

### 6. Workflow - маскирование ошибок
**Ошибка:** Тесты падали, но CI показывал success  
**Файл:** `.github/workflows/ci.yml`  
**Решение:** Убраны `|| echo "..."` команды, которые всегда возвращали 0

### 7. PHPStan - расширения не подключены
**Ошибка:** `Unexpected item 'parameters › symfony'`  
**Файл:** `phpstan.neon`  
**Решение:**
```yaml
includes:
    - vendor/phpstan/phpstan-doctrine/extension.neon
    - vendor/phpstan/phpstan-symfony/extension.neon
```
+ Прогрев кеша Symfony для PHPStan в CI

### 8. workflow_dispatch - неправильный параметр
**Ошибка:** `branches` не поддерживается в `workflow_dispatch`  
**Файл:** `.github/workflows/ci.yml`  
**Решение:** Удален параметр `branches` из секции `workflow_dispatch`

### 9. Security - устаревшая опция
**Ошибка:** `Unrecognized option "enable_authenticator_manager"`  
**Файл:** `config/packages/security.yaml`  
**Решение:** Удалена опция (использовалась для миграции Symfony 5→6, в 7 не нужна)

### 10. PHPStan level 9 - типизация массивов
**Ошибка:** `Property has no value type specified in iterable type array` (32 ошибки)  
**Файлы:** Все Entity классы  
**Решение:** Добавлены PHPDoc аннотации:
```php
/** @var array<string, mixed>|null */
private ?array $conditions = null;

/** @var array<int, string>|null */
private ?array $objectives = null;
```

### 11. Тесты - отсутствие проверок типов
**Ошибка:** PHPStan жалуется на `mixed` типы в тестах  
**Файлы:** `tests/Controller/Api/NPCApiControllerTest.php`, `tests/Service/DialogValidationServiceTest.php`  
**Решение:**
```php
$content = $client->getResponse()->getContent();
$this->assertIsString($content);
$data = json_decode($content, true);
$this->assertIsArray($data);
```

### 12. PHPUnit - KERNEL_CLASS не установлена
**Ошибка:** `You must set the KERNEL_CLASS environment variable`  
**Файл:** `phpunit.xml.dist`  
**Решение:**
```xml
<server name="KERNEL_CLASS" value="App\Kernel" force="true" />
```

### 13. Framework test mode не включен
**Ошибка:** `You cannot create the client used in functional tests if the "framework.test" config is not set to true`  
**Файлы:** `config/packages/test/framework.yaml`, CI workflow, phpunit.xml.dist  
**Решение:**
1. Создан `config/packages/test/framework.yaml`:
   ```yaml
   framework:
       test: true
       session:
           storage_factory_id: session.storage.factory.mock_file
   ```
2. Добавлен warmup test cache в CI
3. Добавлен `APP_DEBUG=1` в phpunit.xml.dist

### 14. Отсутствие тестовых данных
**Ошибка:** `HTTP/1.1 404 Not Found {"error":"NPC not found"}`  
**Файл:** `.github/workflows/ci.yml`  
**Решение:** Добавлена загрузка фикстур перед тестами:
```yaml
- name: Load fixtures for tests
  run: php bin/console doctrine:fixtures:load --no-interaction --env=test
```

## 💡 Улучшения от Bugbot

### 1. JSON валидация в API
**Файлы:** `src/Controller/Api/NPCApiController.php`, `QuestApiController.php`  
**Добавлено:**
```php
$data = json_decode($request->getContent(), true);
if (!is_array($data)) {
    return $this->json(['error' => 'Invalid JSON'], 400);
}
```

### 2. Безопасная обработка JSON в Vue
**Файл:** `assets/components/NPCEditor.vue`  
**Добавлено:**
```javascript
const parseConditions = (conditionsStr) => {
  try {
    return JSON.parse(conditionsStr || '{}');
  } catch (e) {
    console.warn('Invalid JSON:', conditionsStr);
    return {};
  }
};
```

### 3. Удаление узлов с очисткой связей
**Файлы:** `NPCEditor.vue`, `QuestEditor.vue`  
**Исправлено:** Теперь удаляются и все edges, связанные с узлом

### 4. Убраны неиспользуемые импорты
**Файлы:** `NPCEditor.vue`, `QuestEditor.vue`  
**Удалено:** `useVueFlow()` и его деструктурированные переменные

### 5. Улучшены сообщения об ошибках
**Файл:** `NPCEditor.vue`  
**Добавлено:** Вывод детальной информации об ошибке сохранения

## 🚀 Финальная структура CI/CD

```yaml
on:
  push:
    branches-ignore: [main, dev]  # Автоматически
  pull_request:
    branches: ['**']
  workflow_dispatch:  # Вручную для main/dev

jobs:
  tests:
    steps:
      1. Checkout code
      2. Setup PHP 8.3
      3. Validate composer.json
      4. Cache composer deps
      5. Remove symfony.lock
      6. Install PHP deps (--no-scripts)
      7. Run auto-scripts
      8. Setup Node.js 20
      9. Install npm deps
      10. Build assets
      11. Create test DB (via psql)
      12. Run migrations/schema
      13. Warm up dev cache (для PHPStan)
      14. Run PHPStan level 9
      15. Warm up test cache (для PHPUnit)
      16. Run PHPUnit tests
  
  lint:
    steps:
      - Code style check (placeholder)
```

## ✅ Результат

**До:**
- ❌ Composer не устанавливался
- ❌ GraphQL не работал
- ❌ БД не создавалась
- ❌ PHPStan падал на конфигурации
- ❌ PHPUnit не запускался
- ❌ Тесты маскировали ошибки

**После:**
- ✅ Все зависимости устанавливаются
- ✅ GraphQL API работает
- ✅ База данных создается корректно
- ✅ PHPStan level 9 проходит
- ✅ PHPUnit тесты запускаются
- ✅ Все ошибки корректно отображаются

## 📝 Созданные файлы документации

1. **BUGFIXES.md** - история исправлений
2. **NOTES.md** - технические заметки
3. **CI_CD_FIXES.md** - этот файл
4. **README.md** - обновлен Troubleshooting
5. **INSTALL.md** - инструкции по установке
6. **QUICKSTART.md** - быстрый старт

## 🎓 Уроки

### Symfony 7.1 особенности:
- Не использует `enable_authenticator_manager`
- Требует явного `framework.test: true` для WebTestCase
- Doctrine не должен добавлять суффикс если имя БД уже содержит `_test`

### PHPStan level 9:
- Требует явной типизации всех array свойств в PHPDoc
- Нужны проверки типов в тестах (`assertIsString`, `assertIsArray`)
- Требует скомпилированный контейнер Symfony

### PHPUnit 10+:
- Больше нет `PHPUnit\TextUI\Command`
- Используется `PHPUnit\TextUI\Application`
- Требует `KERNEL_CLASS` в конфигурации

### GraphQL Bundle:
- Резолверы вызываются через `@=service()`, не через `@=resolver()`
- Не нужны специальные интерфейсы для резолверов
- Параметры `symfony` и `doctrine` требуют includes расширений

## 🚀 Следующие шаги

1. ✅ CI/CD полностью работает
2. 🔜 Добавить больше тестов (coverage)
3. 🔜 E2E тесты для админки
4. 🔜 Интеграционные тесты для GraphQL
5. 🔜 Настроить деплой в продакшен

---

**Дата последнего обновления:** 2024-11-10  
**Статус:** ✅ Все баги исправлены, CI/CD работает
