PROFILES = --profile admin --profile site

up:
	docker compose up -d
	@echo ""
	@echo "  API:    http://127.0.0.1:8000"
	@echo "  Admin:  http://127.0.0.1:8881"
	@echo "  Site:   http://127.0.0.1:8880"
	@echo ""
	@echo "Waiting for app to start..."

down:
	docker compose $(PROFILES) down

scheduler-logs:
	docker logs -f dashboard_scheduler

scheduler-restart:
	docker compose restart scheduler

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
