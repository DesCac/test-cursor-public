# История исправлений багов

## CI/CD Исправления

### 1. Symfony.lock конфликт с Composer
**Дата:** 2024-11-10  
**Проблема:** `Cannot access offset of type string on string`  
**Решение:** Зафиксирован актуальный `symfony.lock`; Composer запускается без удаления lock-файлов  
**Файл:** `.github/workflows/ci.yml`

### 2. GraphQL resolver функция не найдена
**Дата:** 2024-11-10  
**Проблема:** `The function "resolver" does not exist`  
**Решение:** Переход с `@=resolver()` на `@=service()` для вызова резолверов  
**Файлы:** 
- `config/graphql/types.yaml`
- `src/GraphQL/Resolver/*.php`

### 3. Дублирование суффикса _test в имени БД
**Дата:** 2024-11-10  
**Проблема:** БД называлась `rpg_quest_npc_test_test`  
**Решение:** Отключен `dbname_suffix` в doctrine.yaml для test окружения  
**Файлы:**
- `config/packages/doctrine.yaml`
- `.github/workflows/ci.yml` (прямое создание через psql)

### 4. GraphQL конфигурация - неправильный параметр builders
**Дата:** 2024-11-10  
**Проблема:** `Unrecognized option "resolver" under "overblog_graphql.definitions.builders.field.alias"`  
**Решение:** Удалена секция `builders` из конфигурации  
**Файл:** `config/packages/graphql.yaml`

### 5. PHPUnit использует устаревший API
**Дата:** 2024-11-10  
**Проблема:** `Class "PHPUnit\TextUI\Command" not found`  
**Решение:** Переход на `PHPUnit\TextUI\Application` (PHPUnit 10+)  
**Файл:** `bin/phpunit`

### 6. Тесты не падают в CI/CD
**Дата:** 2024-11-10  
**Проблема:** `|| echo "Tests failed"` маскирует ошибки  
**Решение:** Убраны fallback команды из workflow  
**Файл:** `.github/workflows/ci.yml`

### 7. PHPStan не распознает параметры symfony и doctrine
**Дата:** 2024-11-10  
**Проблема:** `Unexpected item 'parameters › symfony'`  
**Решение:** Добавлены includes для расширений phpstan-symfony и phpstan-doctrine  
**Файл:** `phpstan.neon`

### 8. workflow_dispatch с неправильным параметром branches
**Дата:** 2024-11-10  
**Проблема:** `branches` не поддерживается в `workflow_dispatch`  
**Решение:** Удален параметр `branches` из `workflow_dispatch`  
**Файл:** `.github/workflows/ci.yml`

### 9. enable_authenticator_manager устаревшая опция
**Дата:** 2024-11-10  
**Проблема:** `Unrecognized option "enable_authenticator_manager" under "security"`  
**Решение:** Удалена устаревшая опция - она не нужна в Symfony 7.1  
**Файл:** `config/packages/security.yaml`  
**Причина:** Опция была для миграции Symfony 5.x → 6.x, в 7.x удалена

### 10. PHPStan level 9 - отсутствие типов для array свойств
**Дата:** 2024-11-10  
**Проблема:** `Property has no value type specified in iterable type array`  
**Решение:** Добавлены PHPDoc аннотации со строгими типами для всех array свойств  
**Файлы:**
- `src/Entity/DialogNode.php` - `@var array<string, mixed>|null`
- `src/Entity/DialogConnection.php` - `@var array<string, mixed>|null`
- `src/Entity/Quest.php` - `@var array<int, string>|null` для objectives, `@var array<string, mixed>|null` для rewards/requirements
- `src/Entity/QuestNode.php` - `@var array<string, mixed>|null`
- `src/Entity/QuestConnection.php` - `@var array<string, mixed>|null`

### 11. PHPStan level 9 - проблемы в тестах
**Дата:** 2024-11-10  
**Проблема:** Отсутствие проверок типов перед использованием значений  
**Решение:** Добавлены `assertIsString()` и `assertIsArray()` перед использованием  
**Файлы:**
- `tests/Controller/Api/NPCApiControllerTest.php`
- `tests/Service/DialogValidationServiceTest.php`

### 12. KERNEL_CLASS не установлена для PHPUnit
**Дата:** 2024-11-10  
**Проблема:** `You must set the KERNEL_CLASS environment variable`  
**Решение:** Добавлена переменная в `phpunit.xml.dist`  
**Файл:** `phpunit.xml.dist`  
**Добавлено:** `<server name="KERNEL_CLASS" value="App\Kernel" force="true" />`

### 13. framework.test не включен для WebTestCase
**Дата:** 2024-11-10  
**Проблема:** `You cannot create the client used in functional tests if the "framework.test" config is not set to true`  
**Решение:** 
1. Создан файл конфигурации для test окружения: `config/packages/test/framework.yaml`
2. Добавлен прогрев кеша для test окружения в CI/CD
3. Добавлена переменная `APP_DEBUG=1` в phpunit.xml.dist

**Файлы:**
- `config/packages/test/framework.yaml`
- `.github/workflows/ci.yml` (warmup test cache)
- `phpunit.xml.dist` (APP_DEBUG)

### 14. Отсутствие тестовых данных для PHPUnit
**Дата:** 2024-11-10  
**Проблема:** `{"error":"NPC not found"}` - тест падает, так как в тестовой БД нет данных  
**Решение:** Добавлена загрузка фикстур перед запуском тестов в CI/CD  
**Файл:** `.github/workflows/ci.yml`  
**Команда:** `php bin/console doctrine:fixtures:load --no-interaction --env=test`

## Улучшения качества кода (от Bugbot)

### 1. Валидация JSON в API контроллерах
**Проблема:** `json_decode` не проверял результат  
**Решение:** Добавлена проверка `is_array($data)` с возвратом 400 ошибки  
**Файлы:**
- `src/Controller/Api/NPCApiController.php`
- `src/Controller/Api/QuestApiController.php`

### 2. JSON парсинг с обработкой ошибок в Vue
**Проблема:** `JSON.parse` мог падать при некорректном вводе  
**Решение:** Добавлена функция `parseConditions` с try-catch  
**Файл:** `assets/components/NPCEditor.vue`

### 3. Удаление узлов оставляет висячие связи
**Проблема:** При удалении узла связи оставались в графе  
**Решение:** Фильтрация всех элементов, связанных с удаляемым узлом  
**Файлы:**
- `assets/components/NPCEditor.vue`
- `assets/components/QuestEditor.vue`

### 4. Неиспользуемые импорты useVueFlow
**Проблема:** `addNodes`, `addEdges`, etc. не использовались  
**Решение:** Убраны неиспользуемые деструктурированные переменные  
**Файлы:**
- `assets/components/NPCEditor.vue`
- `assets/components/QuestEditor.vue`

### 5. Улучшение обработки ошибок сохранения
**Проблема:** Неинформативные сообщения об ошибках  
**Решение:** Добавлен вывод детальной информации об ошибке  
**Файл:** `assets/components/NPCEditor.vue`

## Статус

✅ Все баги исправлены  
✅ CI/CD должен проходить успешно  
✅ PHPStan level 9 настроен корректно  
✅ PHPUnit тесты работают  
✅ Все Bugbot замечания учтены

## Рекомендации для будущего

1. **Добавить больше тестов** - покрытие кода тестами
2. **E2E тесты** - для проверки UI редакторов
3. **Интеграционные тесты** - для GraphQL API
4. **Улучшить валидацию** - добавить Symfony Validator для API
5. **Заменить HTTP Basic Auth** - на JWT/OAuth2 для продакшена
