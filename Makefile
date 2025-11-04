# ====== Basic settings ======
DC ?= docker compose
PHP = $(DC) exec php

# ====== .env を用意＆安全な権限にする ======
.PHONY: ensure-env
ensure-env:
	@echo "==> Ensure src/.env"
	@if [ ! -f src/.env ]; then \
		echo "   - creating src/.env from src/.env.example"; \
		cp -n src/.env.example src/.env; \
	fi
	@echo "   - setting permissions to 600 and ownership to current user"
	@chmod 600 src/.env || true
	@chown $$(id -u):$$(id -g) src/.env || true

# ====== DBヘルスチェック待ち ======
.PHONY: wait-mysql
wait-mysql:
	@echo "==> ⏳ Waiting for MySQL (healthcheck)..."
	@until [ "$$($(DC) ps -q mysql | xargs -r docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}starting{{end}}')" = "healthy" ]; do \
		echo "   ... MySQL is starting"; sleep 2; \
	done
	@echo "==> ✅ MySQL is healthy"

# ====== 初回構築 ======
.PHONY: setup
setup: ensure-env
	@echo "==> Docker build & up"
	$(DC) up -d --build
	$(MAKE) wait-mysql
	@echo "==> Laravel setup"
	$(PHP) bash -lc "cd src && composer install && \
		php artisan key:generate || true && \
		php artisan storage:link && \
		php artisan migrate --seed && \
		php artisan optimize"
	@echo "==> Fixing permissions"
	$(PHP) bash -lc "mkdir -p src/storage/logs src/bootstrap/cache && \
		touch src/storage/logs/laravel.log && \
		chown -R www-data:www-data src/storage src/bootstrap/cache && \
		chmod -R 777 src/storage src/bootstrap/cache"
	@echo "✅ Setup complete! Visit: http://localhost  (MailHog: http://localhost:8025)"

# ====== 起動 ======
.PHONY: start
start:
	@echo "==> Starting containers"
	$(DC) up -d
	@$(DC) ps
	@echo "✅ App running at: http://localhost"

# ====== 停止 ======
.PHONY: stop
stop:
	@echo "==> Stopping containers"
	$(DC) stop
	@echo "✅ All containers stopped"
