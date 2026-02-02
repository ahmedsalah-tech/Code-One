<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Blogging Platform - Redis Cache Resilience

This Laravel blogging platform implements graceful error handling for Redis cache failures to ensure high availability.

### Authentication Caching with Graceful Degradation

The application uses a custom `CachedUserProvider` (located at `app/Auth/CachedUserProvider.php`) that caches authenticated user data in Redis for improved performance. When Redis becomes unavailable, the system automatically falls back to direct database queries without disrupting user experience.

#### How It Works

1. **Normal Operation**: User authentication data is cached in Redis (default TTL: 300 seconds)
2. **Cache Failure**: If Redis connection fails, the system:
   - Catches the exception automatically
   - Falls back to querying the database directly
   - Logs a warning for monitoring purposes
   - Returns the user data seamlessly

#### Configuration

The cached user provider is configured in `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'cached_eloquent',
        'model' => App\Models\User::class,
        'cache_store' => env('AUTH_CACHE_STORE', 'redis'),
        'cache_ttl' => env('AUTH_CACHE_TTL', 300),
    ],
],
```

Environment variables:
- `AUTH_CACHE_STORE`: The cache store to use (default: `redis`)
- `AUTH_CACHE_TTL`: Cache time-to-live in seconds (default: `300`)

#### Monitoring

When Redis failures occur, warnings are logged with context including:
- User identifier
- Error message  
- Cache store configuration

Check application logs for entries like:
```
Cache failure in CachedUserProvider, falling back to database
```

#### Testing

Comprehensive tests are available in `tests/Feature/Cache/RedisCacheFailureTest.php` that validate:
- Fallback to database when cache fails
- Error logging
- Different exception types
- Normal cache operation
- Edge cases (non-existent users)

Run tests with:
```bash
php artisan test tests/Feature/Cache/RedisCacheFailureTest.php
```

### Benefits

- **High Availability**: Authentication continues working even when Redis is down
- **No User Impact**: Users experience no authentication failures
- **Monitoring Ready**: Failures are logged for operational visibility
- **Performance**: Benefits from caching when available, gracefully degrades when not

