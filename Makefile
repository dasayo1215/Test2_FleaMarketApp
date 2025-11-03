# ====== Basic settings ======
DC ?= docker compose
PHP = $(DC) exec php

# ====== 初回構築 ======
.PHONY: setup
setup:
	@echo "==> 🐳 Docker build & up"
	$(DC) up -d --build
	@echo "==> 📦 Laravel setup"
	$(PHP) bash -c "composer install && \
		cp -n .env.example .env && \
		php artisan key:generate && \
		php artisan storage:link && \
		php artisan migrate --seed && \
		php artisan optimize"
	@echo "==> 🔒 Fixing permissions"
	$(PHP) bash -c "mkdir -p storage/logs bootstrap/cache && \
		touch storage/logs/laravel.log && \
		chown -R www-data:www-data storage bootstrap/cache && \
		chmod -R 777 storage bootstrap/cache"
	@echo "✅ Setup complete! Visit: http://localhost  (MailHog: http://localhost:8025)"

# ====== 起動 ======
.PHONY: start
start:
	@echo "==> 🚀 Starting containers"
	$(DC) up -d
	@$(DC) ps
	@echo "✅ App running at: http://localhost"
	@echo "📬 MailHog: http://localhost:8025"

# ====== 停止 ======
.PHONY: stop
stop:
	@echo "==> 🛑 Stopping containers"
	$(DC) stop
	@echo "✅ All containers stopped"
