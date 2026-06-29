include .env
export

DOCKER_COMPOSE := $(shell docker compose version > /dev/null 2>&1 && echo "docker compose" || echo "docker-compose")

PROFILES = --profile admin --profile site --profile phpmyadmin

ifneq ("$(wildcard /etc/letsencrypt/live)","")
    COMPOSE_FILES = -f docker-compose.yml -f docker-compose.server.yml
else
    COMPOSE_FILES = -f docker-compose.yml -f docker-compose.override.yml
endif

up:
	$(DOCKER_COMPOSE) $(COMPOSE_FILES) up -d
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
	$(DOCKER_COMPOSE) $(PROFILES) down --remove-orphans

logs:
	$(DOCKER_COMPOSE) logs -f

logs-nginx:
	$(DOCKER_COMPOSE) logs -f nginx

logs-app:
	$(DOCKER_COMPOSE) logs -f app

logs-db:
	$(DOCKER_COMPOSE) logs -f db

scheduler-logs:
	docker logs -f dashboard_scheduler

scheduler-restart:
	$(DOCKER_COMPOSE) restart scheduler

pma:
	$(DOCKER_COMPOSE) --profile phpmyadmin up -d
	@echo ""
	@echo "  phpMyAdmin:  http://127.0.0.1:8080"
	@echo ""

admin:
	$(DOCKER_COMPOSE) --profile admin up -d
	@echo ""
	@echo "  Admin dev:  http://127.0.0.1:5200"
	@echo ""

site:
	$(DOCKER_COMPOSE) --profile site up -d
	@echo ""
	@echo "  Site dev:  http://127.0.0.1:5173"
	@echo ""
