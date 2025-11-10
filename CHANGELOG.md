# Changelog

Все заметные изменения в этом проекте будут документированы в этом файле.

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.0.0/),
и этот проект придерживается [Semantic Versioning](https://semver.org/lang/ru/).

## [Unreleased]

### Добавлено
- Базовая структура Symfony 7.1 проекта
- Графический редактор диалогов NPC на Vue 3
- Графический редактор логики квестов на Vue 3
- GraphQL API для чтения и валидации логики
- Админ-панель с базовой авторизацией
- Docker окружение для разработки
- PHPUnit тесты
- PHPStan статический анализ (level 9)
- Фикстуры с демо-данными
- GitHub Actions CI/CD
- Makefile для удобной работы

### Сущности
- NPC - управление персонажами
- DialogNode - узлы диалоговых деревьев
- DialogConnection - связи между диалоговыми узлами
- Quest - управление квестами
- QuestNode - узлы логики квестов
- QuestConnection - связи между узлами квестов

### API Endpoints
- GET /api/npcs/{id} - получить NPC
- PUT /api/npcs/{id} - обновить NPC
- PUT /api/npcs/{id}/nodes - обновить узлы диалогов
- GET /api/quests/{id} - получить квест
- PUT /api/quests/{id} - обновить квест
- POST /graphql - GraphQL endpoint
- GET /graphiql - GraphQL Playground

### Админка
- Дашборд с обзором
- Список NPC
- Редактор диалогов NPC
- Список квестов
- Редактор логики квестов

## [1.0.0] - 2024-11-10

### Добавлено
- Первый релиз проекта
- Базовая функциональность для управления NPC и квестами

[Unreleased]: https://github.com/youruser/rpg-quest-npc-service/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/youruser/rpg-quest-npc-service/releases/tag/v1.0.0
