# dashboard.loc

Full-stack news/blog platform with real-time features. Laravel 11 API + Vue 3 admin & site frontends.

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
docker compose up -d --build
make up
# Waiting start dashboard_app (first start 2-3m)
docker exec -w /var/www/backend dashboard_app php artisan db:seed
```

## Commands

```bash
make up     # Start core services (nginx, app, db)
make down   # Stop everything including frontend dev servers
make admin  # Start admin frontend dev server  →  http://127.0.0.1:5200
make site   # Start site frontend dev server   →  http://127.0.0.1:5173
```

## Artisan & Composer

```bash
# Enter container shell
docker exec -it dashboard_app bash

# Artisan
docker exec -w /var/www/backend dashboard_app php artisan <command>
docker exec -w /var/www/backend dashboard_app php artisan migrate
docker exec -w /var/www/backend dashboard_app php artisan migrate:rollback
docker exec -w /var/www/backend dashboard_app php artisan test
docker exec -w /var/www/backend dashboard_app php artisan test --filter=TestName

# Seeders
docker exec -w /var/www/backend dashboard_app php artisan db:seed                            # all seeders
docker exec -w /var/www/backend dashboard_app php artisan db:seed --class=AccessesSeeder    # sync admin_accesses keys

# Composer
docker exec -w /var/www/backend dashboard_app composer <command>
docker exec -w /var/www/backend dashboard_app composer require <package>
```

## URLs

| Service          | URL                       |
|------------------|---------------------------|
| Site             | http://127.0.0.1:8880     |
| Admin panel      | http://127.0.0.1:8881     |
| API              | http://127.0.0.1:8000     |
| Admin dev server | http://127.0.0.1:5200     |
| Site dev server  | http://127.0.0.1:5173     |
| phpMyAdmin       | http://127.0.0.1:8080     |
| MySQL (direct)   | 127.0.0.1:8101            |
| WebSocket        | ws://127.0.0.1:6001       |

phpMyAdmin credentials: `root` / `root`

## Project Structure

```
├── backend/            # Laravel 11 API
├── frontend/
│   ├── admin/          # Admin panel (Vue 3 + Tailwind)
│   └── site/           # Public site (Vue 3 + Bootstrap)
├── websocket/          # Node.js WebSocket server
│   ├── server.js       # Entry point — WS + Redis routing
│   ├── redis.js        # ioredis client
│   └── channels/
│       ├── tags.js          # Broadcast tags to all clients
│       ├── article.js       # Per-article comment/like events
│       └── notification.js  # Per-user notifications
└── _docker/            # Nginx templates, PHP-FPM, entrypoint
```

## Real-time Architecture

```
PHP (Predis) ──publish──► Redis ──subscribe──► Node.js ──ws──► Browser
```

| Channel                  | Scope                                         |
|--------------------------|-----------------------------------------------|
| `tags.updated`           | Broadcast to all connected clients            |
| `article.{id}`           | Users currently viewing that article          |
| `notification.{user_id}` | A specific authenticated user                 |

## Rebuild

```bash
docker compose down && docker compose up -d --build
docker restart dashboard_websocket
```
