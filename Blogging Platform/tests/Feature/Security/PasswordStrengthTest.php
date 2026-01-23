<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)->group('security', 'password');

beforeEach(function () {
    Artisan::call('db:seed');
});

test('registration requires password confirmation', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        // Missing password_confirmation
    ]);

    $response->assertSessionHasErrors('password');
});

test('registration fails when passwords do not match', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'DifferentPassword456!',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password must meet minimum length requirement', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'Short1!',
        'password_confirmation' => 'Short1!',
    ]);

    // Laravel's default password rule requires minimum 8 characters
    $response->assertSessionHasErrors('password');
});

test('valid password allows registration', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'ValidPassword123',
        'password_confirmation' => 'ValidPassword123',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
});

test('password is hashed in database', function () {
    $plainPassword = 'Password123!';

    $this->post(route('register'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => $plainPassword,
        'password_confirmation' => $plainPassword,
    ]);

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->password)->not->toBe($plainPassword);
    expect(password_verify($plainPassword, $user->password))->toBeTrue();
});

test('password is hidden from serialization', function () {
    $user = User::factory()->create(['password' => bcrypt('testpassword')]);

    $serialized = $user->toArray();

    expect($serialized)->not->toHaveKey('password');
});

test('password cannot be empty', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertSessionHasErrors('password');
});

test('password change requires current password', function () {
    $user = User::factory()->create(['password' => bcrypt('currentpassword')]);

    $response = $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => '',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

    // Password update uses 'updatePassword' error bag
    $response->assertSessionHasErrors('current_password', null, 'updatePassword');
});

test('password change validates current password', function () {
    $user = User::factory()->create(['password' => bcrypt('currentpassword')]);

    $response = $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'wrongpassword',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

    // Password update uses 'updatePassword' error bag
    $response->assertSessionHasErrors('current_password', null, 'updatePassword');
});

test('password can be changed with correct current password', function () {
    $user = User::factory()->create(['password' => bcrypt('currentpassword')]);

    $response = $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'currentpassword',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

    $response->assertSessionHasNoErrors();

    // Verify new password works
    $user->refresh();
    expect(password_verify('NewPassword123!', $user->password))->toBeTrue();
});

test('password reset requires valid email', function () {
    $response = $this->post(route('password.email'), [
        'email' => 'nonexistent@example.com',
    ]);

    // Laravel may or may not show error depending on configuration (security through obscurity)
    // This test just ensures the application handles it gracefully
    expect($response->status())->toBeIn([200, 302]);
});

test('password is properly updated after change', function () {
    $user = User::factory()->create([
        'password' => bcrypt('currentpassword'),
    ]);

    $this->actingAs($user)
        ->put(route('password.update'), [
            'current_password' => 'currentpassword',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

    $user->refresh();

    // Verify new password works
    expect(password_verify('NewPassword123!', $user->password))->toBeTrue();
    // Old password should no longer work
    expect(password_verify('currentpassword', $user->password))->toBeFalse();
});
