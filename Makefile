.PHONY: help up down build install migrate fixtures test stan

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

up: ## Start all containers
	docker-compose up -d

down: ## Stop all containers
	docker-compose down

build: ## Build containers
	docker-compose build --no-cache

install: ## Install dependencies and setup project
	docker-compose exec php composer install
	docker-compose exec php npm install
	docker-compose exec php npm run build
	docker-compose exec php php bin/console doctrine:database:create --if-not-exists
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

migrate: ## Run database migrations
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

fixtures: ## Load fixtures
	docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction

test: ## Run tests
	docker-compose exec php php bin/phpunit

stan: ## Run PHPStan
	docker-compose exec php vendor/bin/phpstan analyse

shell: ## Access PHP container shell
	docker-compose exec php bash

logs: ## Show logs
	docker-compose logs -f

restart: ## Restart all containers
	docker-compose restart
