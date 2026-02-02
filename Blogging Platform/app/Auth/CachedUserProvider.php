<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CachedUserProvider extends EloquentUserProvider
{
    /** @var string|null */
    protected $cacheStore;

    /** @var int */
    protected $ttlSeconds;

    public function __construct(HasherContract $hasher, string $model, ?string $cacheStore = null, int $ttlSeconds = 300)
    {
        parent::__construct($hasher, $model);
        $this->cacheStore = $cacheStore;
        $this->ttlSeconds = $ttlSeconds;
    }

    public function retrieveById($identifier)
    {
        try {
            $cache = $this->cacheStore
                ? Cache::store($this->cacheStore)
                : Cache::store(config('cache.default'));

            $key = $this->cacheKey($identifier);

            return $cache->remember($key, $this->ttlSeconds, function () use ($identifier) {
                $user = parent::retrieveById($identifier);

                // Avoid caching empties indefinitely; rely on TTL to refresh.
                return $user;
            });
        } catch (\Exception $e) {
            // Log the cache failure for monitoring
            Log::warning('Cache failure in CachedUserProvider, falling back to database', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
                'cache_store' => $this->cacheStore ?? config('cache.default'),
            ]);

            // Fall back to direct database query
            return parent::retrieveById($identifier);
        }
    }

    protected function cacheKey($identifier): string
    {
        return 'auth:user:' . $identifier;
    }
}
