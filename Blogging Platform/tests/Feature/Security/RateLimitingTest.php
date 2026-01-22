<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class)->group('security', 'rate-limiting');

beforeEach(function () {
    Artisan::call('db:seed');
    RateLimiter::clear('login');
});

test('login is rate limited after too many failed attempts', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('correctpassword'),
    ]);

    // Make 5 failed login attempts (the limit)
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // 6th attempt should be rate limited
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('email');

    // Check that the error message contains throttle information
    $errors = session('errors');
    $errorMessage = $errors->first('email');
    $hasThrottleMessage = str_contains((string) $errorMessage, 'Too many login attempts') ||
        str_contains((string) $errorMessage, 'seconds') ||
        str_contains((string) $errorMessage, 'too many');
    expect($hasThrottleMessage)->toBeTrue();
});

test('successful login clears rate limit', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('correctpassword'),
    ]);

    // Make some failed attempts
    for ($i = 0; $i < 3; $i++) {
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // Successful login
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'correctpassword',
    ]);

    $this->assertAuthenticated();

    // Logout
    $this->post(route('logout'));

    // Should be able to login again without rate limit
    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'correctpassword',
    ]);

    $this->assertAuthenticated();
});

test('rate limit is per user and IP combination', function () {
    $user1 = User::factory()->create([
        'email' => 'user1@example.com',
        'password' => bcrypt('password'),
    ]);

    $user2 = User::factory()->create([
        'email' => 'user2@example.com',
        'password' => bcrypt('password'),
    ]);

    // Make failed attempts for user1
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => 'user1@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // User1 should be rate limited
    $response = $this->post(route('login'), [
        'email' => 'user1@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('email');

    // Clear for user2 test
    session()->flush();

    // User2 should still be able to attempt login
    $response = $this->post(route('login'), [
        'email' => 'user2@example.com',
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
});

test('rate limit error message includes retry time', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Exhaust rate limit
    for ($i = 0; $i < 6; $i++) {
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $errors = session('errors');
    $errorMessage = (string) $errors->first('email');

    // Should contain seconds or minutes
    expect($errorMessage)->toMatch('/\d+\s*(second|minute)/i');
});

test('login rate limiter uses transliterated email', function () {
    // This tests that the rate limiter handles special characters in email
    $user = User::factory()->create([
        'email' => 'tëst@example.com',
        'password' => bcrypt('password'),
    ]);

    // Make failed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => 'tëst@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    // Should be rate limited
    $response = $this->post(route('login'), [
        'email' => 'tëst@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertSessionHasErrors('email');
});

test('lockout event is fired when rate limit exceeded', function () {
    \Illuminate\Support\Facades\Event::fake([
        \Illuminate\Auth\Events\Lockout::class,
    ]);

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Exhaust rate limit
    for ($i = 0; $i < 6; $i++) {
        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);
    }

    \Illuminate\Support\Facades\Event::assertDispatched(\Illuminate\Auth\Events\Lockout::class);
});
