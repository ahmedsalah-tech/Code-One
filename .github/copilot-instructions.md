# Copilot Coding Agent Instructions

This repo hosts a Laravel 12 app under `Blogging Platform/`. Use these guidelines to be immediately productive and consistent with the project’s patterns.

## Architecture & Key Components

- **Framework**: Laravel 12 in `Blogging Platform/` following standard MVC.
- **Domains**: Posts, Profiles, Follows, Claps.
- **Models**: `App\Models\Post`, `User`, `Category`, `Clap`, `Follower`.
- **Slugging**: `spatie/laravel-sluggable` for `Post.slug`.
- **Media**: `spatie/laravel-medialibrary` for images and conversions.
- **Auth provider cache**: `App\Auth\CachedUserProvider` registered in `AppServiceProvider` and configured in `config/auth.php` as `driver: cached_eloquent` with `cache_store` (defaults to `redis`) and TTL = 300s.
- **OpenAPI docs**: `darkaonline/l5-swagger` with annotations under `app/` and `routes/`. Generated JSON/YAML lives in `storage/api-docs/` and UI is served at `/api/documentation`.

## Routing & Controllers

- **Route definitions**: See [Blogging Platform/routes/web.php](../Blogging Platform/routes/web.php). Notable patterns:
  - Public profile: `/@{user:username}` → `PublicProfileController@show`.
  - Post show: `/@{username}/{post:slug}` → route model binding to `Post`.
  - CRUD and actions: `post.create|store|edit|update|destroy`, `follow`, `clap` protected by `auth`.
  - Debug endpoints: `/debug/session`, `/debug/auth-cache` with annotated docs.
- **Controller style**: Use route model binding, check ownership (`abort(403)`) for writes, paginate via `simplePaginate(5)`, eager-load `user` and `media`, and `withCount('claps')`. Examples in [Blogging Platform/app/Http/Controllers/PostController.php](../Blogging Platform/app/Http/Controllers/PostController.php).
- **OpenAPI Annotations**: Prefer PHP 8 attributes (`OpenApi\Attributes`) in controllers or annotation classes (e.g., [Blogging Platform/app/OpenApi/Debug.php](../Blogging Platform/app/OpenApi/Debug.php)). Ensure new endpoints are tagged and parameterized consistently.

## Data & Media

- **Migrations**: See [Blogging Platform/database/migrations](../Blogging Platform/database/migrations) for `users`, `posts`, `categories`, `followers`.
- **Media usage**: `Post` uses single `default` collection with conversions `preview (400w)` and `large (1200w)`. `User` has `avatar (128x128 crop)`.
- **Example**: To attach an uploaded image to a post:
  - In controller: `Post::create($data); $post->addMediaFromRequest('image')->toMediaCollection();`
  - Access URL: `$post->imageUrl('preview')` or original if no conversion.

## Developer Workflows

- **Install & run (local)**:
  - `composer install`
  - `npm install`
  - `php artisan key:generate`
  - `php artisan migrate --seed` (factories/seeders present; tests expect seeded `Category`)
  - `composer run dev` (runs `php artisan serve`, `queue:listen`, and `npm run dev` concurrently)
- **Build assets**: `npm run build` (Vite). Inputs configured in [Blogging Platform/vite.config.js](../Blogging Platform/vite.config.js).
- **Swagger docs**: `php artisan l5-swagger:generate` then visit `/api/documentation`; artifacts in [Blogging Platform/storage/api-docs](../Blogging Platform/storage/api-docs).

## Testing

- **Runner**: Pest 4 with Laravel plugin. Typical: `php artisan test` or `vendor/bin/pest`.
- **Browser tests**: Grouped as `browser`; requires Playwright. Install once: `npx playwright install`. Example login + assertions in [Blogging Platform/tests/Feature/Post/PostTest.php](../Blogging Platform/tests/Feature/Post/PostTest.php).
- **Patterns**: Use `uses()->group(...)`, `beforeEach` to `seed()`, `Storage::fake('public')`, `Queue::fake()` for media conversions (`Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob`).

## Conventions & Integration Points

- **Auth caching**: The `users` provider uses `cached_eloquent`; configure `.env` to match the desired store (`AUTH_CACHE_STORE=redis`) and ensure Redis connection is set if used. Debug with `/debug/auth-cache`.
- **Sessions**: Default driver is `database` (see `config/session.php`). `/debug/session` reports session and Redis config for troubleshooting.
- **Routing slugs & usernames**: Use `@{username}` and `{post:slug}` consistently; generate slugs via `spatie/laravel-sluggable`.
- **Queues**: Media conversions run via queues; the dev script starts `queue:listen` automatically.
- **Views & Tailwind**: Blade templates under `resources/views`; Tailwind config in [Blogging Platform/tailwind.config.js](../Blogging Platform/tailwind.config.js).

## Environment: Redis (.env)

- **Redis client**: Predis is used (`predis/predis` is required).
- **Cache store**: Use Redis for app cache and auth caching.
- **Sessions**: Keep `database` by default; optionally switch to Redis.

```env
# Redis base config
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_USERNAME=
REDIS_PASSWORD=
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_CLUSTER=redis

# Cache: use Redis store
CACHE_STORE=redis
REDIS_CACHE_CONNECTION=cache

# Auth provider cache (CachedUserProvider)
AUTH_CACHE_STORE=redis
AUTH_CACHE_TTL=300

# Sessions
SESSION_DRIVER=database
# To use Redis sessions instead:
# SESSION_DRIVER=redis
# SESSION_CONNECTION=default
# SESSION_STORE=redis
```

## When Adding Features

- Add routes in `routes/web.php` with route model binding.
- Annotate endpoints with OpenAPI (attributes) so Swagger stays current.
- Enforce authorization with ownership checks and `auth` middleware groups.
- Extend Feature tests in `tests/Feature/**` following existing Pest style.

If any of these areas are unclear (e.g., environment assumptions, seed data, or Swagger flow), tell me and I’ll refine this doc with specifics from your setup.
