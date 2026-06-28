PROFILES = --profile admin --profile site

COMPOSE_FILES = -f docker-compose.yml
ifneq ("$(wildcard /etc/letsencrypt/live)","")
    COMPOSE_FILES += -f docker-compose.server.yml
endif

up:
	docker compose $(COMPOSE_FILES) up -d
	@echo ""
	@if [ -d /etc/letsencrypt/live ]; then \
		echo "  Site:   https://$$SSL_DOMAIN"; \
		echo "  Admin:  https://$$SSL_DOMAIN$$ADMIN_PATH/"; \
		echo "  API:    https://$$SSL_DOMAIN/api/"; \
	else \
		echo "  Site:   http://127.0.0.1:$$SITE_PORT"; \
		echo "  Admin:  http://127.0.0.1:$$SITE_PORT$$ADMIN_PATH/"; \
		echo "  API:    http://127.0.0.1:$$SITE_PORT/api/"; \
	fi
	@echo ""
	@echo "Waiting for app to start..."

down:
	docker compose $(PROFILES) down

scheduler-logs:
	docker logs -f dashboard_scheduler

scheduler-restart:
	docker compose restart scheduler

pma:
	docker compose --profile phpmyadmin up -d
	@echo ""
	@echo "  phpMyAdmin:  http://127.0.0.1:8080"
	@echo ""

admin:
	docker compose --profile admin up -d
	@echo ""
	@echo "  Admin dev:  http://127.0.0.1:5200"
	@echo ""

site:
	docker compose --profile site up -d
	@echo ""
	@echo "  Site dev:  http://127.0.0.1:5173"
	@echo ""
