.DEFAULT_GOAL := help

.PHONY: help up down restart build logs ps migrate sh clean \
	phpstan cs-check cs-fix rector rector-dry deptrac test qa \
	install env

help: ## Wypisz dostępne cele
	@grep -E '^[a-zA-Z_-]+:.*## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

## --- Środowisko Docker ---

up: ## Zbuduj i uruchom całe środowisko, następnie odpal migracje (jednym poleceniem)
	docker compose up -d --build
	-$(MAKE) migrate

down: ## Zatrzymaj środowisko
	docker compose down

restart: down up ## Zrestartuj całe środowisko

build: ## Zbuduj obrazy bez uruchamiania
	docker compose build

logs: ## Śledź logi wszystkich serwisów
	docker compose logs -f

ps: ## Pokaż status serwisów
	docker compose ps

migrate: ## Uruchom migracje bazy danych
	docker compose run --rm migrate

sh: ## Wejdź do kontenera app
	docker compose exec app sh

clean: ## Zatrzymaj środowisko i usuń wolumeny (KASUJE DANE!)
	docker compose down -v

## --- QA (patrz composer.json) ---

phpstan: ## Statyczna analiza (PHPStan)
	composer phpstan

cs-check: ## Sprawdź styl kodu (PHP-CS-Fixer --dry-run)
	composer cs-check

cs-fix: ## Napraw styl kodu (PHP-CS-Fixer)
	composer cs-fix

rector: ## Zastosuj refaktoryzacje Rectora
	composer rector

rector-dry: ## Podgląd refaktoryzacji Rectora (--dry-run)
	composer rector-dry

deptrac: ## Sprawdź granice architektoniczne (Deptrac)
	composer deptrac

test: ## Uruchom testy (PHPUnit)
	composer test

qa: phpstan cs-check rector-dry deptrac test ## Uruchom pełny zestaw QA przed commitem

## --- Lokalnie bez Dockera ---

install: ## Zainstaluj zależności composera
	composer install

env: ## Skopiuj .env.dist do .env, jeśli jeszcze nie istnieje
	cp -n .env.dist .env
