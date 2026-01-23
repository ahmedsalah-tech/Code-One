# Copilot Coding Agent Instructions

This repo hosts a Laravel 12 app under `Blogging Platform/`. Follow these specifics to stay aligned with project patterns.

## Architecture & Domains

- Laravel MVC; key domains: Posts, Profiles, Follows, Claps. Core models: `Post`, `User`, `Category`, `Clap`, `Follower`.
- Slugs via `spatie/laravel-sluggable`; media via `spatie/laravel-medialibrary` (single `default` collection on `Post`, `avatar` on `User`). Conversions: `Post` → `preview` 400w webp, `large` 1200w webp; `User` → `avatar` 128x128 webp. `Post::imageUrl()` defaults to `preview` (webp) and falls back to original.
- Auth caching: custom `App\Auth\CachedUserProvider` (driver `cached_eloquent`, TTL 300s, store `redis` by default) registered in `AppServiceProvider` and `config/auth.php`.
- Cache headers: `App\Http\Middleware\SetCacheHeaders` appends `Cache-Control: public, max-age=31536000` for static assets (registered in `bootstrap/app.php`).
- OpenAPI: `darkaonline/l5-swagger`; annotations via PHP 8 attributes in controllers or `app/OpenApi`. UI at `/api/documentation`; JSON/YAML in `storage/api-docs/`.

## Routing & Controllers

- Routes in [Blogging Platform/routes/web.php](../Blogging Platform/routes/web.php). Patterns: public profile `/@{user:username}`, post show `/@{username}/{post:slug}`. CRUD/actions (`post.*`, `follow`, `clap`) behind `auth`/`verified`.
- Controller style: route model binding; ownership checks (`abort(403)`) on writes; pagination `simplePaginate(5)`; eager-load `user`, `media`, and `withCount('claps')`. `PostController@show` loads `user`, `category`, `media` to avoid N+1.
- Debug routes: `/debug/session`, `/debug/auth-cache` return session/redis/auth cache info with OpenAPI docs.

## Data & Media

- Migrations under `database/migrations`; seeders expect `Category` present (`php artisan migrate --seed`).
- Media upload pattern: `Post::create($data); $post->addMediaFromRequest('image')->toMediaCollection();` then `$post->imageUrl('preview')` (webp) or original fallback.

## Developer Workflows

- Setup: `composer install`; `npm install`; `php artisan key:generate`; `php artisan migrate --seed`.
- Dev loop: `composer run dev` → runs `php artisan serve`, `php artisan queue:work`, and `npm run dev` concurrently. Queue is required for media conversions; use `QUEUE_CONNECTION=sync` only for debugging.
- Build assets: `npm run build` (Vite with gzip compression plugin in `vite.config.js`).
- Swagger: `php artisan l5-swagger:generate`; open `/api/documentation`.
- Lighthouse: with Brave/Chrome available run `lighthouse http://localhost:8000 --output=html --output-path=./lighthouse-report.html --view` (set `CHROME_PATH` when needed in your shell).

## Testing

- Runner: Pest 4 (`php artisan test` or `vendor/bin/pest`). Browser group uses Playwright (`npx playwright install`).
- Patterns: `uses()->group(...)`, `beforeEach` seeds DB via `Artisan::call('db:seed')`; `Storage::fake('public')`, `Queue::fake()` around media conversions (`Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob`).

### Security Testing (93 tests across 9 files)

Location: `tests/Feature/Security/`. Run: `php artisan test tests/Feature/Security` or `--group=security`.

| Test File                    | Coverage                                                                  |
| ---------------------------- | ------------------------------------------------------------------------- |
| `XssTest.php`                | Script tags, event handlers, javascript: URLs escaped via Blade `{{ }}`   |
| `SqlInjectionTest.php`       | Login, registration, routes protected via Eloquent parameterized queries  |
| `CsrfTest.php`               | Middleware active, `@csrf` in forms, token regeneration                   |
| `AuthenticationTest.php`     | Protected routes, ownership checks (`abort(403)`), email verification     |
| `FileUploadSecurityTest.php` | Malicious files (.php, .exe, .svg) rejected; mime-type validation         |
| `RateLimitingTest.php`       | Login throttled after 5 failures (`LoginRequest::ensureIsNotRateLimited`) |
| `PasswordStrengthTest.php`   | Confirmation, min length, hashing; uses `updatePassword` error bag        |
| `SessionSecurityTest.php`    | Regeneration on login/logout, httponly, same-site attribute               |

**Patterns to follow:**

- Use `(string)` cast when calling `$errors->first('field')` (nullable return).
- Use null-safe operator `auth()->user()?->id` for IDE type safety.
- Profile deletion uses `userDeletion` error bag; password change uses `updatePassword`.

## Conventions & Notes

- Routing slugs/usernames always via `/@{username}` and `{post:slug}`.
- Sessions default to `database` (config in `config/session.php`); Redis optional.
- Tailwind and Blade live under `resources/`; Tailwind config in `tailwind.config.js` (dark mode class strategy).
- Roadmap/status lives in [\_features.md](../_features.md); tracks Dark Mode ✅, Performance ✅, Security Testing ✅, Docker ❌.

If anything is unclear (e.g., queue setup, swagger flow, media conversions), ask for details before coding.
