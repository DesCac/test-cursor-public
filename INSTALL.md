# Инструкция по установке

## Быстрая установка (рекомендуется)

### С использованием Docker и Make

```bash
# 1. Клонировать репозиторий
git clone <your-repo-url>
cd <project-dir>

# 2. Запустить все (одна команда!)
make up && make install
```

Готово! Приложение доступно на http://localhost:8080

### Без Make (альтернатива)

```bash
# 1. Запустить Docker контейнеры
docker-compose up -d

# 2. Установить PHP зависимости
docker-compose exec php composer install

# 3. Установить Node зависимости
docker-compose exec php npm install

# 4. Собрать фронтенд
docker-compose exec php npm run build

# 5. Создать БД
docker-compose exec php php bin/console doctrine:database:create --if-not-exists

# 6. Запустить миграции
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# 7. Загрузить демо-данные
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

## Установка без Docker (для локальной разработки)

### Требования

- PHP 8.3 или выше
- PostgreSQL 16
- Node.js 20
- Composer
- npm

### Шаги установки

1. **Установить зависимости PHP:**
   ```bash
   composer install
   ```
   
   Если возникла ошибка `Cannot access offset of type string on string`, выполните:
   ```bash
   composer install --no-interaction
   ```

2. **Настроить окружение:**
   ```bash
   cp .env .env.local
   # Отредактируйте .env.local с вашими настройками БД
   ```

3. **Установить Node зависимости:**
   ```bash
   npm install
   ```

4. **Собрать assets:**
   ```bash
   npm run build
   ```

5. **Настроить базу данных:**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   php bin/console doctrine:fixtures:load
   ```

6. **Запустить сервер:**
   ```bash
   symfony server:start
   # или
   php -S localhost:8000 -t public/
   ```

## Проверка установки

После установки проверьте:

1. **Админка доступна:**
   - URL: http://localhost:8080/admin
   - Логин: admin
   - Пароль: admin123

2. **GraphQL работает:**
   - URL: http://localhost:8080/graphiql

3. **Тесты проходят:**
   ```bash
   make test
   # или
   docker-compose exec php php bin/phpunit
   ```

4. **PHPStan не находит ошибок:**
   ```bash
   make stan
   # или
   docker-compose exec php vendor/bin/phpstan analyse
   ```

## Проблемы при установке

### Composer не устанавливается

**Проблема:** Ошибка `Cannot access offset of type string on string`

**Решение:**
```bash
docker-compose exec php composer install --no-interaction
```

Лок-файлы уже находятся в репозитории, поэтому удалять `symfony.lock` больше не
нужно. Просто убедитесь, что рабочее дерево чистое, и повторите `make install`.

### База данных не создается

**Проблема:** `Connection refused` к PostgreSQL

**Решение:**
```bash
# Проверьте, что контейнер PostgreSQL запущен
docker-compose ps

# Перезапустите контейнеры
docker-compose down
docker-compose up -d

# Подождите 5-10 секунд и попробуйте снова
docker-compose exec php php bin/console doctrine:database:create
```

### npm build не работает

**Проблема:** `Module not found` или другие ошибки сборки

**Решение:**
```bash
# Очистите кеш и переустановите
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Права доступа

**Проблема:** Permission denied при записи в var/

**Решение:**
```bash
# В Docker
docker-compose exec php chown -R www-data:www-data var/

# Локально
chmod -R 777 var/
# или
sudo chown -R $USER:$USER var/
```

## Следующие шаги

После успешной установки:

1. Прочитайте [QUICKSTART.md](QUICKSTART.md) для быстрого ознакомления
2. Изучите [README.md](README.md) для полной документации
3. Настройте CI/CD (см. раздел в README.md)
4. Измените пароли по умолчанию в `.env.local`

## Получить помощь

Если у вас остались проблемы:

1. Проверьте раздел [Troubleshooting в README.md](README.md#troubleshooting)
2. Посмотрите логи: `make logs` или `docker-compose logs`
3. Создайте issue на GitHub с описанием проблемы
