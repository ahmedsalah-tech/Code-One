<?php

use App\Auth\CachedUserProvider;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class)->group('cache', 'redis', 'resilience');

beforeEach(function () {
    Artisan::call('db:seed');
});

test('CachedUserProvider falls back to database when Redis cache is unavailable', function () {
    // Create a user
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    // Get the cached user provider
    $provider = Auth::getProvider();
    expect($provider)->toBeInstanceOf(CachedUserProvider::class);

    // Mock the cache to throw an exception
    $cacheMock = \Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $cacheMock->shouldReceive('remember')
        ->andThrow(new \Exception('Redis connection refused'));

    // Mock Cache::store to return our mock
    Cache::shouldReceive('store')
        ->with(\Mockery::any())
        ->andReturn($cacheMock);

    // The provider should still be able to retrieve the user from the database
    $retrievedUser = $provider->retrieveById($user->id);

    expect($retrievedUser)->not->toBeNull();
    expect($retrievedUser->id)->toBe($user->id);
    expect($retrievedUser->email)->toBe('test@example.com');
});

test('CachedUserProvider logs warning when Redis fails', function () {
    $user = User::factory()->create();

    // Spy on Log facade
    Log::spy();

    // Get the cached user provider
    $provider = Auth::getProvider();

    // Mock the cache to throw an exception
    $cacheMock = \Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $cacheMock->shouldReceive('remember')
        ->andThrow(new \Exception('Redis connection refused'));

    // Mock Cache::store to return our mock
    Cache::shouldReceive('store')
        ->with(\Mockery::any())
        ->andReturn($cacheMock);

    // Retrieve user should trigger the log
    $provider->retrieveById($user->id);

    // Assert that a warning was logged
    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Cache failure in CachedUserProvider, falling back to database', \Mockery::on(function ($context) use ($user) {
            return $context['identifier'] == $user->id &&
                   isset($context['error']) &&
                   isset($context['cache_store']);
        }));
});

test('CachedUserProvider handles different exception types', function () {
    $user = User::factory()->create();
    $provider = Auth::getProvider();

    // Test with connection exception
    $cacheMock = \Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $cacheMock->shouldReceive('remember')
        ->andThrow(new \Exception('Connection timeout'));

    Cache::shouldReceive('store')
        ->with(\Mockery::any())
        ->andReturn($cacheMock);

    $retrievedUser = $provider->retrieveById($user->id);

    expect($retrievedUser)->not->toBeNull();
    expect($retrievedUser->id)->toBe($user->id);
});

test('CachedUserProvider fallback returns null when user does not exist', function () {
    $provider = Auth::getProvider();

    // Mock the cache to throw an exception
    $cacheMock = \Mockery::mock(\Illuminate\Contracts\Cache\Repository::class);
    $cacheMock->shouldReceive('remember')
        ->andThrow(new \Exception('Redis connection refused'));

    Cache::shouldReceive('store')
        ->with(\Mockery::any())
        ->andReturn($cacheMock);

    // Try to retrieve non-existent user
    $retrievedUser = $provider->retrieveById(99999);

    expect($retrievedUser)->toBeNull();
});

test('CachedUserProvider works normally when cache is available', function () {
    $user = User::factory()->create();
    $provider = Auth::getProvider();

    // Don't mock cache, let it work normally (uses array cache in tests)
    $retrievedUser = $provider->retrieveById($user->id);

    expect($retrievedUser)->not->toBeNull();
    expect($retrievedUser->id)->toBe($user->id);

    // Retrieve again to test caching
    $retrievedUser2 = $provider->retrieveById($user->id);

    expect($retrievedUser2)->not->toBeNull();
    expect($retrievedUser2->id)->toBe($user->id);
});


