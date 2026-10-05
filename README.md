# Drama Admin Panel — Laravel 8

Same Drama panel as the plain-PHP version, rebuilt on **Laravel 8**. Manages **categories** and **videos**, with two public JSON APIs.

- **Single admin**, fixed login — `admin@gmail.com` / `admin123`
- **One-time login**: Laravel "remember me" is forced on, so you stay signed in on the device
- **Categories screen** — image + name, add / edit / delete
- **Videos screen** — category dropdown, upload a video file, list & filter by category
- **Numbered pagination** + indexed queries → fast on very large data (only one page is read at a time)
- **API list screen** with live "Try it"
- **2 public APIs** (JSON, CORS enabled, paginated)

## Requirements
PHP 8.0+, MySQL, Composer. (Built & tested on PHP 8.2 + XAMPP.)

## Setup

Already done in this workspace, but to reproduce on a fresh clone:

```bash
cd laravel
composer install
cp .env.example .env        # then set DB_DATABASE=drama_app, DB_USERNAME=root, DB_PASSWORD=
php artisan key:generate
php artisan migrate:fresh --seed   # creates tables + seeds the admin
php artisan storage:link           # makes uploaded files public
```

`.env` holds the admin credentials used by the seeder:
```
ADMIN_EMAIL=admin@gmail.com
ADMIN_PASSWORD=admin123
```

## Run

**Option A — artisan (quickest):**
```bash
php artisan serve
```
Open http://127.0.0.1:8000/login

**Option B — XAMPP Apache:** point the browser at the `public/` folder:
```
http://localhost/savan_workspace/A_git_code/iptv/laravel/public/login
```

Login: **admin@gmail.com** / **admin123**

## Screens / routes

| Screen | Route |
|---|---|
| Login | `GET /login` |
| Categories | `GET /categories` |
| Videos | `GET /videos` |
| API List | `GET /api-list` |

## APIs

### 1. Get categories
```
GET /api/categories?page=1&per_page=20&search=news
```

### 2. Get videos by category id
```
GET /api/videos?category_id=1&page=1&per_page=20
```

Both return:
```json
{ "success": true, "pagination": { "page":1,"per_page":20,"total":0,"total_pages":1,"has_next":false,"has_prev":false }, "data": [] }
```
`/api/videos` also includes a `category` object. Missing `category_id` → 400; unknown id → 404.

## Large video uploads
App limit is 500 MB (validation in `VideoController`). PHP's own limits must allow it — `public/.htaccess` and `public/.user.ini` raise them, but if your XAMPP ignores both, set in `php.ini` and restart Apache:
```
upload_max_filesize = 512M
post_max_size = 520M
max_execution_time = 300
```

## Where things live
```
app/Models/            Category.php, Video.php
app/Http/Controllers/  CategoryController, VideoController, ApiListController, Auth/LoginController
app/Http/Controllers/Api/  CategoryApiController, VideoApiController
database/migrations/   create_categories_table, create_videos_table
database/seeders/      DatabaseSeeder (seeds the admin)
resources/views/       layouts/app, auth/login, categories/, videos/, api-list, partials/pagination
routes/                web.php (panel), api.php (public APIs)
public/css|js          style.css, app.js
storage/app/public/    images/ + videos/ (uploaded files, served via /storage)
```

## Performance & scale

Built to stay fast with very large data and heavy API traffic:

- **API response caching** — `/api/categories` and `/api/videos` cache their built responses for 60s (keyed by host + params + a global version). Any admin edit bumps the version, so caches refresh instantly. Repeated API hits are served from cache and never touch the DB.
- **Indexed queries** — composite indexes match the exact query patterns: `categories(is_active, sort_order, id)` and `videos(category_id, id)`. Video lookups by category use the index with no filesort.
- **Cached counts** — dashboard `COUNT(*)` totals are cached (60s) so big tables aren't counted on every load.
- **Lazy indexing modal** — the drag-and-drop reorder list loads via AJAX only when opened (capped at 500), so the categories page stays fast regardless of category count.
- **Lean queries** — list/API queries select only needed columns and eager-load `category:id,name` to avoid N+1 and oversized result sets.
- **Pagination caps** — API `per_page` is capped at 100; admin at 10/25/50/100.

### Production tuning (recommended before going live)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
# set in .env: APP_DEBUG=false, APP_ENV=production
# optional: CACHE_DRIVER=redis for shared cache across servers
```
Enable PHP OPcache in `php.ini` (`opcache.enable=1`). For millions of rows, consider a MySQL FULLTEXT index on `categories.name` / `videos.title` if you need infix search (the API currently uses index-friendly prefix search).
