# Code-One — Laravel Blogging Platform

> A production-grade, Medium-inspired publishing platform built with a security-first and performance-first mindset — going well beyond CRUD to tackle real engineering challenges.

[![Laravel](https://img.shields.io/badge/Laravel-10.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker)](https://www.docker.com/)
[![Redis](https://img.shields.io/badge/Redis-Storage-DC382D?style=for-the-badge&logo=redis)](https://redis.io/)
[![Tailwind](https://img.shields.io/badge/Tailwind-UI-06B6D4?style=for-the-badge&logo=tailwindcss)](https://tailwindcss.com/)
[![Pest](https://img.shields.io/badge/Pest-93_Tests-000000?style=for-the-badge&logo=pest)](https://pestphp.com/)
[![Status](https://img.shields.io/badge/Status-100%25_Complete-success?style=for-the-badge)]()

---

## Why I Built This

I started this as a tutorial blogging app. Then I kept asking "but what would break this in production?" and went from there.

The result is a platform that tackles three problems most tutorial projects ignore entirely: **repeated database hits on every authenticated request**, **security vulnerabilities that are trivially exploitable in naive Laravel apps**, and **"works on my machine" environments** that fall apart the moment someone else clones the repo.

The most interesting engineering decision was building a custom `CachedUserProvider` that intercepts `Auth::user()` calls and routes them through Redis instead of MySQL. This turned a ~50ms DB round-trip into a ~1-2ms cache hit on every authenticated page load. Getting cache invalidation right (using Model Observers to bust the key on profile updates) was the part that required the most careful thought.

---

## What Makes This Different From a Standard Blog

| Concern | Standard Tutorial App | This Project |
|---|---|---|
| Auth lookups | MySQL query every request | Redis cache (~1-2ms hit) |
| Security testing | None | 93 dedicated tests |
| Environment | "just run `php artisan serve`" | Full Docker multi-service stack |
| API docs | None | OpenAPI/Swagger UI |
| Vulnerability coverage | None | XSS, SQLi, CSRF, brute-force, malicious uploads |

---

## Architecture & Stack

| Layer | Technology | Purpose |
|---|---|---|
| Backend | Laravel 10 (PHP 8.3) | Application logic, routing, auth |
| Frontend | Blade + Tailwind CSS + Vite | Server-rendered UI with fast asset bundling |
| Cache | Redis | Auth caching, sessions, queue broker |
| Database | MySQL 8.0 | Relational persistence |
| Testing | Pest | Unit, Feature, and Security test suites |
| Containerization | Docker + Nginx | Reproducible multi-service environment |

### Services in Docker Compose
`App` · `Nginx` · `MySQL` · `Redis` · `Queue Worker` · `Mailpit`

---

## Redis Auth Caching — Deep Dive

The core performance feature is a custom `CachedUserProvider` that replaces Laravel's default Eloquent user provider.

**Flow:**
1. Request hits an authenticated route
2. `Auth::user()` is called — provider checks Redis for `auth:user:{id}`
3. **Cache hit** → returns serialized model in ~1-2ms, zero DB queries
4. **Cache miss** → queries MySQL, caches result for 300s (configurable via `AUTH_CACHE_TTL`), returns user

**Cache invalidation** is handled by a Model Observer that calls `Cache::forget('auth:user:{id}')` whenever a user record is updated — preventing stale data without manual cache-busting.

You can verify cache behavior at the debug route:
```
GET /debug/auth-cache
```
Returns a JSON breakdown of session ID, User ID, and DB query count per request.

---

## Security Test Suite (93 Tests)

Security is treated as a feature, not an afterthought. The suite covers:

- **XSS** — Validates script tags and malicious event handlers are escaped in all output
- **SQL Injection** — Ensures all inputs go through Eloquent/PDO parameterization
- **CSRF** — Verifies token enforcement on all state-changing routes
- **Rate Limiting** — Login and API routes protected against brute-force
- **File Upload Security** — Blocks `.php`, `.sh`, `.exe` and validates MIME types server-side

```bash
# Run the full security suite
docker exec -it blogging_app php artisan test --group=security

# Run all tests
docker exec -it blogging_app php artisan test
```

---

## Getting Started

### Option A: Docker (Recommended)

```bash
# 1. Clone
git clone https://github.com/ahmedsalah-tech/Full-Fledged-Laravel-Blogging-Platform.git
cd "Full-Fledged-Laravel-Blogging-Platform/Blogging Platform"

# 2. Configure environment
cp .env.example .env

# 3. Initialize and start all services
chmod +x docker-setup.sh && ./docker-setup.sh
docker-compose up -d

# 4. Run migrations with seed data
docker exec -it blogging_app php artisan migrate --seed
```

Access points:
- App: `http://localhost:8000`
- Mailpit (email testing): `http://localhost:8025`
- Swagger API docs: `http://localhost:8000/api/documentation`

### Option B: Local (without Docker)

Requires PHP 8.3, Composer, MySQL 8, Redis.

```bash
composer install
cp .env.example .env
# Set DB_HOST=127.0.0.1, configure REDIS_HOST
php artisan key:generate
php artisan migrate --seed
npm install && npm run dev
php artisan serve
```

---

## 📸 Screenshots

### Dashboard — Light Mode
![Dashboard Light](https://github.com/ahmedsalah-tech/Full-Fledged-Laravel-Blogging-Platform/blob/main/Screenshots/Dashboard(Light).png)

### Dashboard — Dark Mode
![Dashboard Dark](https://github.com/ahmedsalah-tech/Full-Fledged-Laravel-Blogging-Platform/blob/main/Screenshots/Dashboard(Light).png)

### Single Post View
![Single Post](https://github.com/ahmedsalah-tech/Full-Fledged-Laravel-Blogging-Platform/blob/main/Screenshots/Single-Post.png)

### API Documentation (Swagger UI)
![API Docs](https://github.com/ahmedsalah-tech/Full-Fledged-Laravel-Blogging-Platform/blob/main/Screenshots/API-Docs.png)

> 📂 View all screenshots (login, register, create post, profile, email inbox, Redis cache): [/screenshots](https://github.com/ahmedsalah-tech/Full-Fledged-Laravel-Blogging-Platform/tree/main/Screenshots)

---

## DevOps & CI/CD Design

The Docker setup is built with production promotion in mind:

- **Development image:** Includes Xdebug, hot-reloading, unoptimized assets
- **Production image:** Multi-stage build — lean PHP-FPM image with minified Vite assets only
- **Health checks:** DB and Redis readiness verified before migrations run in the pipeline
- **Rollback triggers:** Deployment fails health checks → automatic rollback

---

## Debugging & Monitoring

| Tool | Access | Purpose |
|---|---|---|
| Auth Cache Inspector | `GET /debug/auth-cache` | Verify Redis hits vs DB queries |
| Swagger UI | `GET /api/documentation` | Interactive API testing |
| Mailpit | `http://localhost:8025` | Inspect outgoing emails locally |
| Redis CLI | `redis-cli -n 1 KEYS "*auth:user:*"` | Monitor live cache keys |
| Query Logger | `DB_LOG_QUERIES=true` in `.env` | Profile DB performance |

---

## Troubleshooting

**Profile updates not reflecting?**
This is the Redis cache TTL at work. Clear manually via Tinker:
```php
Cache::store('redis')->forget('auth:user:1');
```
Or wait for the 300s TTL to expire.

**Docker can't connect to MySQL?**
Set `DB_HOST=db` (the Docker service name) in `.env`, not `127.0.0.1`.

---

## What I'd Do Differently

- Add event sourcing for the post lifecycle (draft → published → archived) instead of a simple status enum
- Implement a read-replica MySQL setup and route SELECT queries to the replica
- Move image uploads to S3-compatible storage (currently local disk)

---

## Contributing

1. Fork the repo
2. Create a branch: `git checkout -b feature/your-feature`
3. Ensure all tests pass: `php artisan test`
4. Open a PR with a clear description of what changed and why
