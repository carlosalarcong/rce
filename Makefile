.DEFAULT_GOAL := help
DC  = docker compose
PHP = $(DC) exec php

help: ## Lista los comandos
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Levanta los contenedores
	$(DC) up -d --wait

down: ## Baja los contenedores
	$(DC) down

sh: ## Shell en el contenedor PHP
	$(PHP) sh

install: ## Instala dependencias de Composer
	$(PHP) composer install

test: ## Corre todos los tests
	$(PHP) bin/phpunit

stan: ## PHPStan nivel max
	$(PHP) php bin/console cache:warmup -q
	$(PHP) vendor/bin/phpstan analyse --memory-limit=1G

deptrac: ## Reglas de dependencia entre módulos y capas
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac.modules.yaml --no-progress
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml --no-progress

cs: ## Revisa estilo de código
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Corrige estilo de código
	$(PHP) vendor/bin/php-cs-fixer fix

check: rules cs stan deptrac test ## Todo lo que debe pasar antes de terminar una tarea

.PHONY: help up down sh install test stan deptrac cs cs-fix check rules

rules: ## Reglas que no cubren PHPStan ni Deptrac
	@! grep -rnE 'setDbPassword\(|getDbPassword\(|db_password' src/ || { echo 'La clave de las bases de tenant no se lee ni se guarda en tenant_db_config (docs/adr/0003)'; exit 1; }
