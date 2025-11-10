# 🎮 RPG Quest & NPC Service

Централизованный сервис для настройки логики NPC и квестов для RPG roguelike игр.

## 📋 Описание

Этот проект предоставляет полноценную платформу для управления NPC и квестами в RPG играх:

- **Графический редактор диалогов** - интуитивный интерфейс для создания сложных диалоговых деревьев
- **Редактор логики квестов** - визуальное проектирование логики квестов с условиями и наградами
- **GraphQL API** - мощное API для чтения логики и валидации действий игроков
- **Админ-панель** - защищенная базовой авторизацией панель управления
- **Фикстуры** - готовые демо-данные для быстрого старта разработки

## 🛠 Технологический стек

- **Backend**: Symfony 7.1 (LTS), PHP 8.3+
- **Frontend**: Vue 3 + VueFlow (графический редактор)
- **Database**: PostgreSQL 16 с JSON полями
- **API**: GraphQL (overblog/graphql-bundle)
- **Инфраструктура**: Docker, Docker Compose, Nginx
- **Качество кода**: PHPStan (level 9), PHPUnit
- **CI/CD**: GitHub Actions

## 🚀 Быстрый старт (2 команды)

### Предварительные требования

- Docker и Docker Compose
- Make (опционально, для удобства)

### Развертывание

```bash
# 1. Клонируйте репозиторий
git clone <repository-url>
cd <project-directory>

# 2. Запустите все сервисы
make up && make install
```

Всё! Проект развернут и готов к работе.

### Альтернатива без Make

```bash
# 1. Запустите Docker контейнеры
docker-compose up -d

# 2. Установите зависимости и настройте БД
docker-compose exec php composer install
docker-compose exec php npm install
docker-compose exec php npm run build
docker-compose exec php php bin/console doctrine:database:create --if-not-exists
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

## 🌐 Доступ к приложению

После развертывания сервис доступен по следующим адресам:

- **Админ-панель**: http://localhost:8080/admin
  - Логин: `admin`
  - Пароль: `admin123`
- **GraphQL Playground**: http://localhost:8080/graphiql
- **API Endpoint**: http://localhost:8080/graphql

## 📁 Структура проекта

```
.
├── assets/                  # Vue приложения для редакторов
│   ├── components/
│   │   ├── NPCEditor.vue   # Редактор диалогов NPC
│   │   └── QuestEditor.vue # Редактор логики квестов
│   ├── npc-editor.js
│   └── quest-editor.js
├── config/                  # Конфигурация Symfony
│   ├── graphql/            # GraphQL схемы
│   └── packages/           # Конфигурация бандлов
├── docker/                  # Docker конфигурация
│   ├── nginx/
│   └── php/
├── public/                  # Публичная директория
├── src/
│   ├── Controller/         # Контроллеры
│   │   ├── AdminController.php
│   │   └── Api/
│   ├── Entity/             # Doctrine сущности
│   │   ├── NPC.php
│   │   ├── Quest.php
│   │   ├── DialogNode.php
│   │   ├── DialogConnection.php
│   │   ├── QuestNode.php
│   │   └── QuestConnection.php
│   ├── GraphQL/            # GraphQL резолверы
│   │   └── Resolver/
│   ├── Service/            # Бизнес-логика
│   │   └── DialogValidationService.php
│   └── DataFixtures/       # Демо-данные
├── templates/               # Twig шаблоны
├── tests/                   # Тесты
├── docker-compose.yml
├── Dockerfile
├── Makefile
└── README.md
```

## 🎯 Основные функции

### 1. Управление NPC

- Создание и редактирование NPC
- Графический редактор диалоговых деревьев
- Поддержка условий для диалогов
- Различные типы узлов: start, dialog, choice, action, end

### 2. Управление квестами

- Создание квестов с целями и наградами
- Визуальный редактор логики квеста
- Поддержка условий и требований
- Типы узлов: start, objective, condition, reward, end

### 3. GraphQL API

Примеры запросов:

```graphql
# Получить NPC с диалогами
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
      connections {
        id
        choiceText
        targetNode {
          id
        }
      }
    }
  }
}

# Валидация выбора в диалоге
query {
  validateDialogChoice(npcId: 1, nodeId: 2, choiceId: 1) {
    valid
    message
    nextNodeId
  }
}

# Получить квест
query {
  quest(id: 1) {
    id
    name
    description
    objectives
    rewards
    requirements
  }
}
```

## 🧪 Тестирование

### Запуск тестов

```bash
# Все тесты
make test

# Или напрямую
docker-compose exec php php bin/phpunit
```

### Запуск PHPStan

```bash
# Статический анализ (level 9)
make stan

# Или напрямую
docker-compose exec php vendor/bin/phpstan analyse
```

## 📦 Работа с фикстурами

```bash
# Загрузить демо-данные
make fixtures

# Или напрямую
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

Демо-данные включают:
- 2 NPC с готовыми диалоговыми деревьями (Торговец и Мудрец)
- 2 квеста с полной логикой (Главный и побочный квест)

## 🔧 Полезные команды

```bash
make help          # Показать все доступные команды
make up            # Запустить контейнеры
make down          # Остановить контейнеры
make build         # Пересобрать контейнеры
make install       # Установить зависимости и настроить проект
make migrate       # Выполнить миграции
make fixtures      # Загрузить фикстуры
make test          # Запустить тесты
make stan          # Запустить PHPStan
make shell         # Войти в контейнер PHP
make logs          # Показать логи
make restart       # Перезапустить контейнеры
```

## 🔄 CI/CD (GitHub Actions)

### Настройка GitHub Actions Runner

Проект настроен для работы с GitHub Actions. Пайплайн выполняет:
- Запуск PHPUnit тестов
- Проверку кода с PHPStan (level 9)
- Валидацию composer.json
- Сборку фронтенда

### Правила запуска

- **Автоматический запуск**: при push в любую ветку, кроме `main` и `dev`
- **Ручной запуск**: для веток `main` и `dev` (через GitHub UI)

### Локальный запуск GitHub Actions Runner

1. Установите GitHub Actions runner локально:

```bash
# Создайте директорию для runner
mkdir actions-runner && cd actions-runner

# Скачайте runner (для Linux x64)
curl -o actions-runner-linux-x64-2.311.0.tar.gz -L https://github.com/actions/runner/releases/download/v2.311.0/actions-runner-linux-x64-2.311.0.tar.gz

# Распакуйте
tar xzf ./actions-runner-linux-x64-2.311.0.tar.gz
```

2. Настройте runner:

```bash
# Конфигурация (потребуется токен из настроек репозитория)
./config.sh --url https://github.com/YOUR_USERNAME/YOUR_REPO --token YOUR_TOKEN

# Для самостоятельного runner без регистрации на GitHub:
# используйте act: https://github.com/nektos/act
```

3. Запустите runner:

```bash
./run.sh
```

### Использование act для локального тестирования CI

```bash
# Установите act (https://github.com/nektos/act)
brew install act  # macOS
# или
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash  # Linux

# Запустите workflow локально
act push

# Запустите конкретную job
act -j tests
```

### Настройка секретов

Если в будущем понадобятся секреты для деплоя:

1. Откройте Settings → Secrets and variables → Actions в GitHub
2. Добавьте необходимые секреты:
   - `DATABASE_URL` (для продакшена)
   - `APP_SECRET`
   - и т.д.

## 🗄️ Структура базы данных

### Таблицы

- **npcs** - Информация о NPC
- **dialog_nodes** - Узлы диалоговых деревьев
- **dialog_connections** - Связи между узлами диалогов
- **quests** - Информация о квестах
- **quest_nodes** - Узлы логики квестов
- **quest_connections** - Связи между узлами квестов

### JSON поля

Условия и данные хранятся в JSON формате для гибкости:
```json
{
  "level": 5,
  "quest_completed": ["quest_1", "quest_2"],
  "has_item": "ancient_key"
}
```

## 🔐 Безопасность

- Админ-панель защищена HTTP Basic Auth
- GraphQL API доступен публично (добавьте авторизацию при необходимости)
- Пароли в .env должны быть изменены для продакшена
- Используйте HTTPS в продакшене

## 📝 Настройка переменных окружения

Скопируйте `.env` в `.env.local` и настройте:

```bash
# Основные настройки
APP_ENV=dev
APP_SECRET=your-secret-key

# База данных
DATABASE_URL="postgresql://app:app_password@postgres:5432/rpg_quest_npc"

# Базовая авторизация админки
ADMIN_USER=admin
ADMIN_PASSWORD=your-secure-password
```

## 🚢 Деплой в продакшен

### 1. Подготовка

```bash
# Установите APP_ENV=prod
# Сгенерируйте безопасный APP_SECRET
# Настройте правильный DATABASE_URL
```

### 2. Оптимизация

```bash
composer install --no-dev --optimize-autoloader
npm run build
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### 3. Миграции

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

## 🐛 Troubleshooting

### Ошибка при установке Composer (Symfony Flex)

Если при запуске `composer install` возникает ошибка:
```
Cannot access offset of type string on string
```

**Решение:**
```bash
# Удалите symfony.lock и попробуйте снова
rm symfony.lock
composer install --no-scripts
composer run-script auto-scripts
```

Или в CI/CD эта проблема уже решена автоматически в workflow файле.

### Проблемы с правами доступа

```bash
sudo chown -R $USER:$USER .
chmod +x bin/console bin/phpunit
```

### Проблемы с БД

```bash
# Пересоздать БД
docker-compose exec php php bin/console doctrine:database:drop --force
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
```

### Проблемы с npm

```bash
# Очистить кеш
rm -rf node_modules package-lock.json
npm install
```

### CI/CD не проходит тесты

**Проблема с composer install:**

Если GitHub Actions падает на этапе установки зависимостей:

1. Проверьте, что в репозитории **НЕ** закоммичен `composer.lock` 
2. Файл `symfony.lock` автоматически удаляется в workflow
3. Если проблема продолжается, попробуйте обновить зависимости локально:
   ```bash
   rm symfony.lock composer.lock
   composer update
   git add composer.lock
   git commit -m "Update composer.lock"
   ```

**Проблема с GraphQL:**

Если видите ошибку `The function "resolver" does not exist`:
- Это уже исправлено в текущей версии
- GraphQL резолверы теперь вызываются через `@=service()`
- Убедитесь, что используете последнюю версию кода

**Проблема с базой данных:**

Если видите `database "rpg_quest_npc_test_test" does not exist`:
- Это уже исправлено - убран лишний суффикс `_test`
- БД создается напрямую через psql в CI/CD
- Локально используйте стандартные команды Symfony

## 📚 Дополнительная документация

- [INSTALL.md](INSTALL.md) - Подробная инструкция по установке
- [QUICKSTART.md](QUICKSTART.md) - Быстрый старт
- [CONTRIBUTING.md](CONTRIBUTING.md) - Как участвовать в разработке
- [SECURITY.md](SECURITY.md) - Безопасность
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [GraphQL Bundle](https://github.com/overblog/GraphQLBundle)
- [VueFlow](https://vueflow.dev/)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)

## 🤝 Участие в разработке

1. Fork репозитория
2. Создайте feature branch (`git checkout -b feature/amazing-feature`)
3. Commit изменения (`git commit -m 'Add amazing feature'`)
4. Push в branch (`git push origin feature/amazing-feature`)
5. Откройте Pull Request

## 📄 Лицензия

MIT License

## 👥 Авторы

Разработано для RPG Roguelike проекта

---

**Важно**: Это dev-версия. Перед использованием в продакшене обязательно измените пароли, настройте HTTPS и добавьте дополнительные меры безопасности.
