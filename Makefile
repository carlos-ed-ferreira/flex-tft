DC = docker-compose -f laradock/docker-compose.yml --env-file laradock/.env
MYSQL_ROOT_PASSWORD = $(shell grep -E '^MYSQL_ROOT_PASSWORD=' laradock/.env 2>/dev/null | cut -d= -f2)

.PHONY: prepare-laradock start-containers create-database-run up down stop dev setup shell logs create-database migrate seed test sync strip-comments strip-comments-run format format-check chown help
.DEFAULT_GOAL := help

help:
	@printf "\n"
	@printf "  %-20s %s\n" "make dev" "Sobe containers, migra e inicia Vite + queue + pail (foreground)"
	@printf "  %-20s %s\n" "make setup" "Setup inicial: deps, .env, key, migrate, seed, build"
	@printf "  %-20s %s\n" "make up" "Sobe containers em background"
	@printf "  %-20s %s\n" "make stop" "Para os containers sem remove-los"
	@printf "  %-20s %s\n" "make down" "Para e remove os containers"
	@printf "  %-20s %s\n" "make create-database" "Cria o banco principal (se necessario)"
	@printf "  %-20s %s\n" "make migrate" "Roda php artisan migrate"
	@printf "  %-20s %s\n" "make seed" "Roda db:seed (seeder=NomeSeeder opcional)"
	@printf "  %-20s %s\n" "make test" "Roda a suite de testes PHPUnit"
	@printf "  %-20s %s\n" "make sync" "Sincroniza dados TFT da Community Dragon (--set=N opcional)"
	@printf "  %-20s %s\n" "make strip-comments" "Remove comentarios do codigo em app/ e roda format"
	@printf "  %-20s %s\n" "make format" "Formata codigo PHP (Pint) e JS/Vue (Prettier)"
	@printf "  %-20s %s\n" "make format-check" "Verifica formatacao sem aplicar"
	@printf "  %-20s %s\n" "make shell" "Abre bash no workspace (como usuario laradock)"
	@printf "  %-20s %s\n" "make chown" "Corrige permissoes dos arquivos em app/"
	@printf "  %-20s %s\n" "make logs" "Exibe logs em tempo real (mysql, nginx, workspace)"
	@printf "\n"

prepare-laradock:
	@echo ">> Inicializando submodules (se necessario)..."
	@[ -f laradock/.env.example ] || git submodule update --init --recursive
	@[ -f laradock/.env.example ] || (echo "ERRO: Laradock nao foi inicializado. Rode 'git submodule update --init --recursive'." && exit 1)
	@echo ">> Configurando laradock/.env..."
	@[ -f laradock/.env ] || cp laradock/.env.example laradock/.env
	@sed -i 's|^APP_CODE_PATH_HOST=.*|APP_CODE_PATH_HOST=../app|' laradock/.env
	@sed -i 's|^COMPOSE_PROJECT_NAME=.*|COMPOSE_PROJECT_NAME=flex-tft|' laradock/.env

start-containers:
	@echo ">> Subindo containers (mysql, nginx, workspace)..."
	@$(DC) up -d mysql nginx workspace
	@echo ">> Aguardando MySQL ficar pronto..."
	@for i in $$(seq 1 30); do \
		$(DC) exec -T mysql mysqladmin ping -uroot -p$(MYSQL_ROOT_PASSWORD) --silent 2>/dev/null \
			&& echo ">> MySQL pronto." && exit 0; \
		printf "   aguardando... ($$i/30)\r"; \
		sleep 2; \
	done; \
	echo "" && echo "ERRO: MySQL nao respondeu em 60 segundos." && exit 1

up: prepare-laradock start-containers

stop:
	@$(DC) stop

down:
	@$(DC) down

create-database-run:
	@echo ">> Criando banco principal (se necessario)..."
	@$(DC) exec -T mysql mysql -uroot -p$(MYSQL_ROOT_PASSWORD) \
		-e "CREATE DATABASE IF NOT EXISTS \`flex-tft\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

create-database: up create-database-run

migrate: create-database
	@echo ">> Rodando migrations..."
	@$(DC) exec -T workspace bash -c "cd /var/www && php artisan migrate --force"

seed: up
	@echo ">> Rodando seeders..."
	@$(DC) exec -T workspace bash -c "cd /var/www && php artisan db:seed --force $(if $(seeder),--class=$(seeder),)"

setup: prepare-laradock
	@echo ">> [1/8] Configurando app/.env..."
	@[ -f app/.env ] || cp app/.env.example app/.env
	@echo ">> [2/8] Configurando app/.env.testing..."
	@[ -f app/.env.testing ] || cp app/.env.testing.example app/.env.testing
	@echo ">> [3/8] Subindo containers..."
	@$(MAKE) -s start-containers
	@echo ">> [4/8] Instalando dependencias PHP..."
	@$(DC) exec -T workspace bash -c "cd /var/www && composer install"
	@echo ">> [5/8] Gerando chave da aplicacao..."
	@$(DC) exec -T workspace bash -c "cd /var/www && php artisan key:generate"
	@echo ">> [6/8] Criando banco principal..."
	@$(MAKE) -s create-database-run
	@echo ">> [7/8] Rodando migrations..."
	@$(DC) exec -T workspace bash -c "cd /var/www && php artisan migrate --force"
	@echo ">> Rodando seeders..."
	@$(DC) exec -T workspace bash -c "cd /var/www && php artisan db:seed --force"
	@echo ">> [8/8] Instalando dependencias JS e gerando build..."
	@$(DC) exec -T workspace bash -c "cd /var/www && npm install && npm run build"
	@echo ""
	@echo ">> Setup concluido! Rode 'make dev' para iniciar o desenvolvimento."

dev: migrate
	@echo ">> Iniciando servidores (queue | pail | vite)..."
	@$(DC) exec workspace bash -c "cd /var/www && npx concurrently \
		'php artisan queue:listen --tries=1 --timeout=0' \
		'php artisan pail --timeout=0' \
		'npm run dev' \
		--names=queue,pail,vite --kill-others"

test: up
	@echo ">> Criando banco de testes (se necessario)..."
	@$(DC) exec -T mysql mysql -uroot -p$(MYSQL_ROOT_PASSWORD) \
		-e "CREATE DATABASE IF NOT EXISTS \`flex-tft-testing\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null
	@echo ">> Rodando testes..."
	@$(DC) exec -T workspace bash -c "cd /var/www && php artisan config:clear --ansi && php artisan test"

sync: up
	@$(DC) exec -T workspace bash -c "cd /var/www && php artisan tft:sync $(if $(set),--set=$(set),)"

strip-comments: format

strip-comments-run: up
	@$(DC) exec -T workspace bash -c "cd /var/www && npm run strip-comments"

format: strip-comments-run
	@$(DC) exec -T workspace bash -c "cd /var/www && npx concurrently \"prettier --write .\" \"./vendor/bin/pint\" --names=prettier,pint"

format-check: up
	@$(DC) exec -T workspace bash -c "cd /var/www && npm run strip-comments:check && npx concurrently \"prettier --check .\" \"./vendor/bin/pint --test\" --names=prettier,pint"

shell:
	@$(DC) exec --user laradock workspace bash

chown: up
	@$(DC) exec -T workspace bash -c "chown -R laradock:laradock /var/www"

logs:
	@$(DC) logs -f mysql nginx workspace
