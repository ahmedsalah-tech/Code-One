<p align="center">
  <img alt="Code-One" src="https://img.shields.io/badge/Code--One-Project-blue?style=flat" />
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-FF2D20?style=flat&logo=laravel&logoColor=white" />
  <img alt="PHP" src="https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white" />
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white" />
  <img alt="Redis" src="https://img.shields.io/badge/Redis-D82C20?style=flat&logo=redis&logoColor=white" />
  <img alt="Docker" src="https://img.shields.io/badge/Docker-2496ED?style=flat&logo=docker&logoColor=white" />
  <img alt="Vite" src="https://img.shields.io/badge/Vite-646CFF?style=flat&logo=vite&logoColor=white" />
  <img alt="Tailwind" src="https://img.shields.io/badge/Tailwind-38B2AC?style=flat&logo=tailwindcss&logoColor=white" />
  <img alt="GitHub Actions" src="https://img.shields.io/badge/GitHub_Actions-2088FF?style=flat&logo=github-actions&logoColor=white" />
  <img alt="Swagger" src="https://img.shields.io/badge/OpenAPI-Swagger-brightgreen?style=flat&logo=swagger&logoColor=white" />
  <img alt="Testing" src="https://img.shields.io/badge/Testing-Pest-FF3B3B?style=flat&logo=phpunit&logoColor=white" />
</p>

# Code‑One — Laravel Blogging Platform

Short description
- Medium-like blogging platform built with Laravel (app code under `Blogging Platform/`). Includes containerized dev stack (Docker), Redis-backed auth caching, Vite/Tailwind front-end tooling, OpenAPI docs, and a large security test suite.

Repository
- https://github.com/ahmedsalah-tech/Code-One
- Primary app path: Blogging Platform/

Table of contents
- Project overview & goals
- Features (implemented & planned)
- Architecture & directory map
- Quickstart: Docker (recommended)
- Quickstart: Local (non-Docker)
- Important env variables & config
- Auth + Redis caching — detailed (how it works + code)
- Cache invalidation patterns & recommended observer
- Debugging & verification (commands & endpoints)
- Testing & security details
- CI/CD and deployment notes
- Production hardening & monitoring
- Troubleshooting & FAQ
- Contribution guidelines
- Next steps / recommended improvements

---

Project overview & goals
- Deliver a production‑grade blogging platform with secure auth, performant asset pipeline, and reproducible dev environment.
- Emphasis on security (automated tests), performance (Redis, Vite), and developer ergonomics (Docker + setup scripts).
- Project targets: user posts, profiles, follows, claps, media handling (Spatie medialibrary patterns referenced).

Features (implemented / notable)
- Authentication: standard Laravel auth flows (register/login/logout, password reset, email verification patterns present).
- Auth caching: custom CachedUserProvider to cache user lookup in Redis (TTL configurable).
- Sessions: configurable session driver; documentation and tests ensure session regeneration and fixation prevention.
- DevOps: Dockerfile, docker-compose (app, nginx, mysql, redis, queue, mailpit), docker-setup.sh script.
- Frontend: Vite + Tailwind CSS; build optimized with gzip plugins.
- Media: Spatie medialibrary patterns referenced (webp conversions).
- API docs: OpenAPI (l5-swagger) annotations; UI at /api/documentation.
- Security tests: comprehensive suite (XSS, SQLi, CSRF, auth bypass, file upload security, rate limiting, password strength, session security).
- Debug routes: /debug/auth-cache and related for validating caching behaviour.

Architecture & directory map (high level)
- Blogging Platform/
  - app/ — Controllers, Models, Auth provider (App\Auth\CachedUserProvider), Providers
  - config/ — auth.php, cache.php, session.php, database.php (redis config)
  - routes/ — web.php, auth.php (auth routes)
  - resources/views/ — blade templates (auth/login.blade.php, etc.)
  - tests/ — Pest test files (Feature/Auth, Feature/Security)
  - docker/ & Dockerfile / docker-compose.yml
  - _features.md — implementation roadmap & notes
  - README.md (this file)

Quickstart — Docker (recommended)
1. Clone repo
   git clone https://github.com/ahmedsalah-tech/Code-One.git
2. cd "Code-One/Blogging Platform"
3. Copy environment template and edit if needed
   cp .env.example .env
   (set APP_URL, DB, REDIS if you change compose defaults)
4. Make setup script executable and run
   chmod +x docker-setup.sh
   ./docker-setup.sh
   (script prepares volumes and may run migrations)
5. Start services
   docker-compose up -d
6. Inside app container (if not handled by script):
   docker exec -it <app_container> php artisan key:generate
   docker exec -it <app_container> php artisan migrate --seed
7. Open app
   http://localhost:8000 (port depends on docker-compose)

Services & ports (typical)
- app (php-fpm) — internal service
- nginx — 80 -> maps to host (commonly 8000)
- mysql — 3306 (container)
- redis — 6379 (container)
- mailpit — 8025 (mail UI)
- queue worker — background service for media conversions

Quickstart — Local (no Docker)
1. Prereqs: PHP 8+, Composer, Node (npm), MySQL/SQLite
2. In Blogging Platform/:
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   npm run dev
   php artisan serve --host=127.0.0.1 --port=8000
3. Run queue worker for media tasks:
   php artisan queue:work

Important environment variables & config (high value)
- APP_NAME, APP_URL, APP_ENV, APP_KEY
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- CACHE_STORE — default in config/cache.php (database); can be redis/file/etc.
- AUTH_CACHE_STORE — default: redis — used by CachedUserProvider (config/auth.php)
- AUTH_CACHE_TTL — default: 300 (seconds). Controls how long cached user remains.
- SESSION_DRIVER — e.g., database, redis. If redis, set SESSION_STORE.
- REDIS_HOST, REDIS_PORT, REDIS_DB, REDIS_CACHE_DB, REDIS_PREFIX
- QUEUE_CONNECTION
- L5_SWAGGER_* envs (Swagger UI configuration)

Auth + Redis caching — deep dive (how it works, code & behaviors)

1) Where it’s wired
- Provider registration: app/Providers/AppServiceProvider.php
  - registers `cached_eloquent` provider that constructs App\Auth\CachedUserProvider.
- Provider configured: config/auth.php
  - users provider uses 'driver' => 'cached_eloquent'
  - 'cache_store' => env('AUTH_CACHE_STORE', 'redis')
  - 'cache_ttl' => env('AUTH_CACHE_TTL', 300)

2) The provider (app/Auth/CachedUserProvider.php)
- Key parts (actual code present in repo):

```php
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Cache;

class CachedUserProvider extends EloquentUserProvider
{
    protected $cacheStore;
    protected $ttlSeconds;

    public function __construct($hasher, string $model, ?string $cacheStore = null, int $ttlSeconds = 300)
    {
        parent::__construct($hasher, $model);
        $this->cacheStore = $cacheStore;
        $this->ttlSeconds = $ttlSeconds;
    }

    public function retrieveById($identifier)
    {
        $cache = $this->cacheStore
            ? Cache::store($this->cacheStore)
            : Cache::store(config('cache.default'));

        $key = $this->cacheKey($identifier);

        return $cache->remember($key, $this->ttlSeconds, function () use ($identifier) {
            return parent::retrieveById($identifier);
        });
    }

    protected function cacheKey($identifier): string
    {
        return 'auth:user:' . $identifier;
    }
}
```

3) Runtime flow (request lifecycle)
- On a request that needs authenticated user ($request->user(), auth()->user()):
  - Laravel resolves user via guard → provider::retrieveById($id).
  - CachedUserProvider attempts to read key `auth:user:{id}` from configured cache store (usually Redis).
  - If found and not expired → returns cached (deserialized) User model (no DB query).
  - If missing/expired → calls parent::retrieveById($id) (DB), caches the returned model (or null) for TTL seconds.

4) Where Redis is configured
- config/cache.php — defines 'stores' -> 'redis' store, and cache prefix (CACHE_PREFIX).
- config/database.php -> 'redis' connection(s): default (db 0) and cache (db 1).
- config/session.php — session driver/store; sessions may also use Redis independently.

5) Actual cached item
- The code caches the Eloquent User model instance. Laravel serializes the model (attributes/relations present at time of serialization) into cache.
- The final key stored in Redis will be prefixed by the cache prefix defined in config/cache.php.

Cache invalidation: problems & solutions (must read)
- Problem: cached user may become stale when profile/roles/permissions change.
- Current provider does not auto-invalidate on model changes.
- Recommended solutions:
  1. Model observer to forget the cached key on save/delete.
  2. Use a lightweight DTO in cache (only required fields) instead of the full model.
  3. Use versioned keys or cache tags (if driver supports tags) to allow bulk invalidation.
  4. Shorten TTL for fields that change frequently. Combine with targeted invalidation.

Sample observer to auto-invalidate (add to repo)
- Create: app/Observers/UserObserver.php

```php
<?php
namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserObserver
{
    public function saved(User $user)
    {
        Cache::store(config('auth.providers.users.cache_store'))->forget('auth:user:'.$user->getAuthIdentifier());
    }

    public function deleted(User $user)
    {
        Cache::store(config('auth.providers.users.cache_store'))->forget('auth:user:'.$user->getAuthIdentifier());
    }
}
```

- Register observer in AppServiceProvider::boot():

```php
use App\Models\User;
use App\Observers\UserObserver;

public function boot()
{
    User::observe(UserObserver::class);

    // existing Auth::provider(...) registration...
}
```

This ensures user updates immediately clear the cache for that user.

Debugging & verification (commands & endpoints)

1) Debug endpoint
- GET /debug/auth-cache (protected by auth middleware)
  - Returns: username, user_id, session_id, DB_query_count or queries list.
  - Use it to prove cache hits vs misses:
    - First authenticated request after login: DB_query_count > 0 (cache miss).
    - Second request within TTL: DB_query_count ≈ 0 (cache hit).

2) Redis CLI examples (consider cache prefix)
- Determine prefix: config('cache.prefix') or env CACHE_PREFIX
- List keys (cache DB index normally REDIS_CACHE_DB, default 1):
  redis-cli -h $REDIS_HOST -p $REDIS_PORT -n $REDIS_CACHE_DB KEYS 'auth:user:*'
- Check TTL:
  redis-cli -n $REDIS_CACHE_DB TTL "<prefixed_key>"
- Get serialized value (not human friendly; Laravel may use PHP serialize or other serializer):
  redis-cli -n $REDIS_CACHE_DB GET "<prefixed_key>"
- Delete key:
  redis-cli -n $REDIS_CACHE_DB DEL "<prefixed_key>"

3) From Laravel Tinker (preferred — respects store/prefix):
- Open tinker:
  php artisan tinker
- Forget cached user:
  Cache::store(config('auth.providers.users.cache_store'))->forget('auth:user:'.$id);

Testing & security (how to run & what to expect)
- Test runner: Pest (php artisan test or vendor/bin/pest)
- Run all tests:
  php artisan test
- Run security tests group:
  php artisan test --group=security
- Example auth tests exist at:
  tests/Feature/Auth/AuthenticationTest.php
  - Test cases: render login, login success, login failure, logout
- Session & auth cache tests:
  tests/Feature/Security/SessionSecurityTest.php — asserts session regeneration, auth-cache debug endpoint auth protections.

CI/CD & deployment notes
- GitHub Actions workflows (present) build docker images for PRs and push to Docker Hub on merges to main.
- Docker images are built using Dockerfile in Blogging Platform/ and pushed via workflow on success.
- For production:
  - Use secure Docker registry credentials in Actions secrets.
  - Use managed Redis (or robust Redis cluster) and monitor eviction/ memory.
  - Use HTTPS, secure session cookies, HSTS headers and rate limiting.

Production hardening & monitoring
- Invalidate auth cache on user change (observer above).
- Do not cache sensitive or frequently changing authorization data indefinitely.
- Consider storing only safe subset of user fields (id, username, avatar_url) and fetch critical authorization fields from DB or a separate cache key invalidated on role/perm changes.
- Monitor Redis: memory usage, evictions (INFO MEMORY), key counts for cache DB.
- Add app metrics (Prometheus, NewRelic) and alerting for:
  - High DB query counts per request
  - Redis memory usage > threshold
  - Elevated auth failures / rate limiting events
  - Queue backlogs

Recommended improvements (priority list)
1. Auto-invalidate auth cache on User updates (observer) — high priority.
2. Replace full model caching with a small DTO if you only need a few fields.
3. Add cache tagging or versioned keys to support bulk invalidation of user caches.
4. Add TTL & eviction monitoring dashboards for Redis.
5. Implement the dark mode UI (mentioned in _features.md).
6. Document and include sample docker-compose.override.yml for local env overrides (ports, memory).
7. Add smoke tests / end-to-end tests (Playwright) for core flows.

Troubleshooting & FAQ
- “Why doesn’t my profile change show immediately?” — cached user still in Redis; either wait TTL or clear key via tinker/observer.
- “Redis keys not found with redis-cli KEYS?” — ensure you’re using the correct DB index (REDIS_CACHE_DB) and prefix.
- “Session lost after login” — check SESSION_DRIVER and SESSION_STORE in .env; ensure session config maps to Redis if expecting Redis sessions.
- “Dev stack fails to start” — docker-compose ps and docker-compose logs <service> are your friends. Check volume permissions.

Contribution guidelines
- Fork → branch feature/<name> → write tests → update _features.md/README if applicable → open PR.
- Keep PR scope focused and include testing instructions.
- Tag reviewers and ensure CI passes.

Appendix — Useful commands & snippets
- Clear caches:
  php artisan cache:clear
  php artisan config:clear
  php artisan route:clear
- Tinker:
  php artisan tinker
  >>> Cache::store(config('auth.providers.users.cache_store'))->get('auth:user:1');
- Redis CLI (example):
  redis-cli -h 127.0.0.1 -p 6379 -n 1 KEYS "auth:user:*"
