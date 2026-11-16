.DEFAULT_GOAL := help
DC  = docker compose
# -T: sin terminal, para que funcione también desde el hook de Git y la CI.
PHP = $(DC) exec -T php

help: ## Lista los comandos
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

## ── Desarrollo ──────────────────────────────────────────────
up: ## Levanta los contenedores
	$(DC) up -d --wait

down: ## Baja los contenedores
	$(DC) down

sh: ## Shell en el contenedor PHP
	$(DC) exec php sh

install: ## Instala dependencias de Composer
	$(PHP) composer install --no-interaction --no-progress

dev: up install hooks ## Deja el entorno listo: contenedores, dependencias y hooks

## ── Calidad ─────────────────────────────────────────────────
test: test-db ## Corre todos los tests
	$(PHP) bin/phpunit

test-db: ## Prepara la base Main de pruebas (rce_main_test) con sus migraciones
	$(PHP) bin/console doctrine:database:create --env=test --if-not-exists -q
	$(PHP) bin/console doctrine:migrations:migrate --env=test -n -q --allow-no-migration

cuenta: ## Crea una cuenta para entrar al RCE: make cuenta EMAIL=tu@correo.cl
	@[ -n "$(EMAIL)" ] || { echo "Falta el correo: make cuenta EMAIL=tu@correo.cl"; exit 1; }
	$(DC) exec php bin/console rce:identity:create-account "$(EMAIL)"

test-unit: ## Solo tests unitarios
	$(PHP) bin/phpunit --testsuite unit

test-external: ## Tests contra ambientes de prueba reales de proveedores (internet)
	$(PHP) bin/phpunit --group external

stan: ## PHPStan nivel max
	$(PHP) php bin/console cache:warmup -q
	$(PHP) vendor/bin/phpstan analyse --memory-limit=1G --no-progress

deptrac: ## Reglas de dependencia entre módulos y capas
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac.modules.yaml --no-progress
	$(PHP) vendor/bin/deptrac analyse --config-file=deptrac.layers.yaml --no-progress

cs: ## Revisa estilo de código
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Corrige estilo de código
	$(PHP) vendor/bin/php-cs-fixer fix

lint: ## YAML, Twig, contenedor de servicios y composer.json
	$(PHP) composer validate --strict --no-check-publish
	$(PHP) php bin/console lint:yaml config --parse-tags
	$(PHP) php bin/console lint:twig templates
	$(PHP) php bin/console lint:container

rules: ## Reglas que no cubren PHPStan ni Deptrac
	@! grep -rnE 'setDbPassword\(|getDbPassword\(|db_password' src/ || { echo 'La clave de las bases de tenant no se lee ni se guarda en tenant_db_config (docs/adr/0003)'; exit 1; }
	@bash scripts/check-adapters.sh

ci-rapida: rules cs lint stan deptrac test-unit ## Lo que corre el hook antes de cada push (menos de un minuto)

check: rules cs lint stan deptrac test ## Todo lo que debe pasar antes de terminar una tarea (lo que corre la CI)

## ── Hook y runner (docs/ci.md) ──────────────────────────────
hooks: ## Activa los hooks de Git del proyecto (pre-push → make ci-rapida)
	git config core.hooksPath scripts/git-hooks
	@echo "Hooks activos: antes de cada git push corre make ci-rapida (en un apuro: git push --no-verify)."

runner: ## Construye, registra y levanta el runner propio de GitHub Actions
	bash scripts/runner/runner.sh iniciar

runner-logs: ## Lo que está haciendo el runner
	bash scripts/runner/runner.sh logs

runner-detener: ## Detiene el runner (queda registrado)
	bash scripts/runner/runner.sh detener

runner-quitar: ## Da de baja el runner en GitHub y lo borra de este equipo
	bash scripts/runner/runner.sh quitar

.PHONY: help up down sh install dev test test-db cuenta test-unit test-external stan deptrac cs cs-fix lint rules ci-rapida check hooks runner runner-logs runner-detener runner-quitar
