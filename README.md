# dashboard.loc

Full-stack news/blog platform with real-time features. Laravel 11 API + Vue 3 admin & site frontends.

**Live demo:** https://demo-news.duckdns.org

## Stack

| Layer            | Technology                                               |
|------------------|----------------------------------------------------------|
| Backend          | Laravel 11, PHP 8.2                                      |
| Database         | MySQL 8.0                                                |
| Cache / Pub-Sub  | Redis 7 (Predis)                                         |
| WebSocket        | Node.js 20 (ws + ioredis)                                |
| Admin frontend   | Vue 3 + TypeScript, Vite, Pinia, Tailwind CSS, VeeValidate |
| Site frontend    | Vue 3 + TypeScript, Vite, Pinia, Bootstrap 4.6           |
| Infrastructure   | Docker Compose, Nginx, PHP-FPM                           |

## Requirements

- Docker + Docker Compose
- Make
- WSL2 (Ubuntu)

## Setup

```bash
cp .env.example .env && cp backend/.env.example backend/.env
# Edit .env and backend/.env with your values
docker compose up -d --build
make up
# First start takes 2-3 minutes
docker exec -w /var/www/backend dashboard_app php artisan db:seed
```

## Commands

```bash
make up     # Start core services — auto-detects local vs server
make down   # Stop everything
make admin  # Start admin frontend dev server  →  http://127.0.0.1:5200
make site   # Start site frontend dev server   →  http://127.0.0.1:5173
make pma    # Start phpMyAdmin                 →  http://127.0.0.1:8080
```

## Artisan & Composer

```bash
# Enter container shell
docker exec -it dashboard_app bash

docker exec -w /var/www/backend dashboard_app php artisan <command>
docker exec -w /var/www/backend dashboard_app php artisan migrate
docker exec -w /var/www/backend dashboard_app php artisan migrate:rollback
docker exec -w /var/www/backend dashboard_app php artisan test
docker exec -w /var/www/backend dashboard_app php artisan test --filter=TestName

# Seeders
docker exec -w /var/www/backend dashboard_app php artisan db:seed
docker exec -w /var/www/backend dashboard_app php artisan db:seed --class=AccessesSeeder

# Composer
docker exec -w /var/www/backend dashboard_app composer require <package>
```

## URLs (local)

| Service          | URL                              |
|------------------|----------------------------------|
| Site             | http://127.0.0.1:8880            |
| Admin panel      | http://127.0.0.1:8880/admin/     |
| API              | http://127.0.0.1:8880/api/       |
| Admin dev server | http://127.0.0.1:5200            |
| Site dev server  | http://127.0.0.1:5173            |
| phpMyAdmin       | http://127.0.0.1:8080 (make pma) |
| MySQL (direct)   | 127.0.0.1:8101                   |
| WebSocket        | ws://127.0.0.1:6001              |

## Environment

Root `.env` controls Docker/Nginx. `backend/.env` controls Laravel. Never merge them.

| Variable     | Default  | Description                     |
|--------------|----------|---------------------------------|
| `SITE_PORT`  | `8880`   | Exposed port (local)            |
| `ADMIN_PATH` | `/admin` | URL prefix for admin panel      |

To change admin path: set `ADMIN_PATH=/manage` in root `.env` and `VITE_ADMIN_BASE=/manage/` in `frontend/admin/.env.*`, then rebuild.

## Nginx routing (single port)

All traffic enters on `SITE_PORT`, routed by path:

- `/api/*` → Laravel PHP-FPM
- `/storage/*` → Laravel public storage
- `/${ADMIN_PATH}/*` → Admin Vue SPA
- `/*` → Site Vue SPA

## Docker Compose files

| File                        | Purpose                                      |
|-----------------------------|----------------------------------------------|
| `docker-compose.yml`        | Base config — all environments               |
| `docker-compose.override.yml` | Local dev — auto-loaded, adds port & phpMyAdmin |
| `docker-compose.server.yml` | Server only — SSL, ports 80/443              |

`make up` auto-detects the environment: if `/etc/letsencrypt/live` exists, `docker-compose.server.yml` is included automatically.

### Server setup

Create `docker-compose.server.yml` on the server (see template in `_docker/nginx/conf.d/templates-ssl/`) and add to root `.env`:

```env
SSL_DOMAIN=yourdomain.com
SITE_PORT=443
ADMIN_PATH=/admin
```

## Project Structure

```
├── backend/                  # Laravel 11 API
├── frontend/
│   ├── admin/                # Admin panel (Vue 3 + Tailwind)
│   └── site/                 # Public site (Vue 3 + Bootstrap)
├── websocket/                # Node.js WebSocket server
│   ├── server.js             # Entry point — WS + Redis routing
│   ├── redis.js              # ioredis client
│   └── channels/
│       ├── tags.js           # Broadcast tags to all clients
│       ├── article.js        # Per-article comment/like events
│       └── notification.js   # Per-user notifications
└── _docker/
    ├── nginx/conf.d/
    │   ├── templates/        # HTTP templates (local)
    │   └── templates-ssl/    # HTTPS templates (server)
    ├── app/                  # PHP-FPM Dockerfile + entrypoint
    └── ...
```

## Real-time Architecture

```
PHP (Predis) ──publish──► Redis ──subscribe──► Node.js ──ws──► Browser
```

| Channel                  | Scope                             |
|--------------------------|-----------------------------------|
| `tags.updated`           | Broadcast to all connected clients |
| `article.{id}`           | Users viewing that article        |
| `notification.{user_id}` | A specific authenticated user     |

## Rebuild

```bash
docker compose down && docker compose up -d --build
docker restart dashboard_websocket
```
