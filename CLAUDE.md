# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Code Style Rules — ALWAYS follow these

**NO alignment spaces.** Single space before `=`, `=>`, `:` — never pad to align columns.

```php
// WRONG
$output   = trim(...);
'title'   => $this->title,

// RIGHT
$output = trim(...);
'title' => $this->title,
```

```ts
// WRONG
const is_loading   = ref(true)
const page_title   = ref('')

// RIGHT
const is_loading = ref(true)
const page_title = ref('')
```

**NO objects/arrays on one line.** Every property on its own line, always — no exceptions, even for short objects.

```ts
// WRONG
{ name: 'name', model: null, placeholder: 'Name', type: 'text' },
{ key: 'id', text: 'ID' },
{ key: 'name', text: 'Name' },
...(condition ? [{ key: 'actions', text: '' }] : []),

// RIGHT
{
    name: 'name',
    model: null,
    placeholder: 'Name',
    type: 'text',
},
{
    key: 'id',
    text: 'ID',
},
{
    key: 'name',
    text: 'Name',
},
...(condition ? [{
    key: 'actions',
    text: '',
}] : []),
```

**snake_case** for all variables, object keys, props, interface fields. camelCase/PascalCase only for functions, components, file names.

**English only** — all code comments, docblocks, and inline notes must be in English. No other languages.

**Validation in FormRequest only** — never `$request->validate([...])` inside a controller. Always create a class under `app/Http/Requests/` and type-hint it in the method signature.

**Axios `.then()` only** — never async/await for HTTP calls.

**Always `127.0.0.1`** — never `localhost`.

**SVGs in admin are Vue components** — never write inline `<svg>` markup inside component templates in the admin frontend.

- All icons live in `frontend/admin/src/components/icons/` as single-file Vue components (e.g. `IconBell.vue`, `IconClose.vue`).
- **Before creating a new icon, check `components/icons/` first** — reuse an existing one if the shape matches.
- Icon component template: just the `<svg>` element as the root, no `<script>` block needed. Vue's attr inheritance automatically forwards `class`, `width`, `height` from the parent.
- **Size and color are always set from the outside** (via Tailwind `class` or `width`/`height` attrs on the component). Never hardcode size classes inside an icon component.
- Filled icons: `fill="currentColor"` on `<svg>`. Stroke icons: `stroke="currentColor"` and `fill="none"` on `<svg>`. The parent controls color via `text-{color}` Tailwind classes.

```html
<!-- WRONG — inline SVG in template -->
<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path d="..." />
</svg>

<!-- RIGHT — imported icon component, size/color set from outside -->
<IconReply class="h-4 w-4" />
<IconBell width="20" height="20" />
```

---

## Stack

- **Backend**: Laravel 11, PHP 8.2, MySQL 8.0, Redis (cache + pub/sub), Predis
- **Frontend admin**: Vue 3 + TypeScript, Vite, Pinia, Vue Router, Tailwind CSS, VeeValidate + Yup
- **Frontend site**: Vue 3 + TypeScript, Vite, Pinia, Vue Router, Bootstrap 4.6
- **WebSocket**: Node.js (ws + ioredis), port 6001, Docker service `dashboard_websocket`
- **Infrastructure**: Docker Compose — Nginx + PHP-FPM + MySQL + Redis + Node.js

## Development Commands

All commands run from WSL terminal in the project root (`~/projects/dashboard.loc`).

```bash
make up        # Start all services, waits for PHP-FPM ready, prints URLs
make down      # Stop everything
make admin     # Start admin frontend dev server — http://127.0.0.1:5200
make site      # Start site frontend dev server — http://127.0.0.1:5173

docker exec -it -w /var/www/backend dashboard_app php artisan <command>
docker exec -it -w /var/www/backend dashboard_app composer <command>
docker exec -it -w /var/www/backend dashboard_app php artisan test
docker exec -it -w /var/www/backend dashboard_app php artisan test --filter=TestName
```

URLs: Site `http://127.0.0.1:8880`, Admin `http://127.0.0.1:8880/admin/`, API `http://127.0.0.1:8880/api/`

All traffic runs on a single port (`SITE_PORT`), routed by Nginx path:
- `/api/*` → Laravel PHP-FPM
- `/storage/*` → Laravel public storage
- `/ws/` → WebSocket proxy → `websocket:6001` (nginx proxies WSS→WS in production)
- `/${ADMIN_PATH}/*` → Admin Vue SPA (default `/admin`)
- `/*` → Site Vue SPA

Root `.env` controls Docker/Nginx (`SITE_PORT`, `ADMIN_PATH`, `SSL_DOMAIN`). `backend/.env` controls Laravel. Never merge them.

## Docker Compose files

| File | Purpose |
|------|---------|
| `docker-compose.yml` | Base — all environments |
| `docker-compose.override.yml` | Local dev — auto-loaded, adds port & phpMyAdmin profile |
| `docker-compose.server.yml` | Server only — SSL (ports 80/443), nginx templates-ssl |

`make up` auto-detects: if `/etc/letsencrypt/live` exists → includes `docker-compose.server.yml`.  
Makefile auto-detects `docker compose` vs `docker-compose` via shell check — no manual sed needed.

## Frontend env files

`frontend/admin/.env.production` and `frontend/site/.env.production` are **NOT in git** (`git rm --cached` was run). Edit manually on server after each `git pull`. Local dev uses `.env.local`.

`VITE_WS_URL` must end with `/ws/` (trailing slash) — nginx location `^~ /ws/` requires it.

## Architecture

### Routing (Backend)

Routes under `backend/routes/`:
- `auth.php` — site user auth — prefix `/api/`
- `admin.php` — admin panel API — prefix `/api/admin/`, protected by `admin` middleware
- `api.php` — site API (layout, news, search, tags, subscribe)

### Auth & Permissions

Two guards: `web` (site users, `User` model) and `admin` (`AdminUsers` model).  
Admin permissions: `AdminUsers → rule_id → AdminRules → accesses_id` (JSON `{ module: { view: bool, edit: bool } }`).  
Checked via `middleware('access:module.action')`.

### API Response Format

All controllers use `RespondTrait`:
- Success: `{ "data": ..., "status": 200 }`
- Error: `{ "status": 4xx, "errors": ... }`

### Backend Services

Business logic lives in services, not controllers. Key services:
- `ArticleService` — paginated articles by category/subcategory/tag, search, view counter
- `CategoryService`, `HomeService`, `LayoutService`
- `TagAdminService` — publishes `tags.updated` to Redis after any tag mutation
- `ImageService`, `AdminService`, `UserService`, `DebugService`

### Redis & WebSocket

Laravel uses Redis for caching (tagged cache via `Cache::tags()`).  
For pub/sub publishing, use raw `new \Predis\Client()` — Laravel applies a key prefix that breaks channel names if you use `Redis::publish()` directly.

```php
$client = new \Predis\Client(['host' => env('REDIS_HOST'), 'port' => env('REDIS_PORT')]);
$client->publish('channel.name', $payload);
```

WebSocket server (`websocket/server.js`) subscribes to Redis channels and broadcasts to connected browsers. Currently handles `tags.updated`.

### Frontend Site — Key Patterns

**Breadcrumbs:** defined in `router.ts` via `meta.breadcrumbs` — a resolver function `(route, store) => Breadcrumb[]`. Resolved by `useBreadcrumbs` composable. `AppHeader` just renders the result. To add breadcrumbs for a new page — add a resolver to the `breadcrumbs` object in `router.ts`.

**Tags:** `BaseTagsBlock` accepts `Tag[]` (not `string[]`), links to `news_tag` route.

**Search & Tag pages:** single `NewsFilterPage.vue` component, detects mode via `route.name === 'news_search'`.

**Layout store:** loaded once, includes `connectWebSocket()` for real-time tag updates.

### Frontend Admin — Key Patterns

**`EditPage.vue`** — generic form component. `@submit` passes `(values, actions)` — `setErrors` is in the **second argument**:
```ts
function onSubmit(_: any, { setErrors }: any) { ... }
```

**`EditPage` prop `clear_on_submit`** — pass `:clear_on_submit="true"` to auto-clear form fields after successful submit.

**Modals — never write `<Teleport>` + backdrop div manually.** Always use `BaseModal` (`components/base/BaseModal.vue`). Put modal content in the `#body` slot. Emit `@close` to close. Example:
```html
<BaseModal v-if="show" @close="show = false">
    <template #body>
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900 mx-4">
            ...
        </div>
    </template>
</BaseModal>
```

**Admin tables — never write `<table>` manually.** Always use:
- `BaseTablePagination` — when the list needs server-side pagination
- `BaseTable` — when data is already loaded (no pagination needed)

`BaseTable` props: `headers`, `table`, `row_class?`, `row_attrs?`, `plain?`. Emits `row_click`. Slots: `#prepend` (extra row before data, e.g. add-new form), named slot per column key `#col="{ data }"`.  
Use `row_attrs` to spread extra attributes/events onto `<tr>` (e.g. drag-and-drop handlers).  
Pass `plain` when nesting inside a custom card wrapper (e.g. `TableCard`) to skip the outer `div`.

`BaseTablePagination` extras:
- `:params="{ key: value }"` — extra filters merged into request payload; `watch(params)` auto-reloads on change
- `defineExpose({ reload })` — call `table.value?.reload()` from parent after mutations
- Pagination detection via `'current_page' in data.data` (not `data.data.total` — falsy on 0)
- Shows "No entries" when the result list is empty

### Docker

- `docker-compose.override.yml` — dev frontend services with profiles `admin` / `site`, not started by default
- PHP-FPM as `www-data` UID/GID 1000:1000 — no chown needed
- `make up` waits for `ready to handle connections` in dashboard_app logs before printing URLs
- `_docker/app/entrypoint.sh` — composer install, migrations, storage:link, frontend build, php-fpm

### Migrations

Loaded from two directories (configured in `AppServiceProvider`):
- `database/migrations/` — base tables
- `database/migrations/admin/` — admin tables
