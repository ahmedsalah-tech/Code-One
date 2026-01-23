<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('security', 'sql-injection');

beforeEach(function () {
    Artisan::call('db:seed');
});

test('SQL injection in login email field is prevented', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Attempt SQL injection in email field
    $response = $this->post(route('login'), [
        'email' => "test@example.com' OR '1'='1",
        'password' => 'wrongpassword',
    ]);

    // Should fail authentication, not bypass it
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('SQL injection in login password field is prevented', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $response = $this->post(route('login'), [
        'email' => 'test@example.com',
        'password' => "' OR '1'='1",
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('SQL injection in post title is prevented', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    $maliciousTitle = "Test'; DROP TABLE posts; --";

    $response = $this->actingAs($user)
        ->post(route('post.store'), [
            'title' => $maliciousTitle,
            'content' => 'Safe content',
            'category_id' => $category->id,
            'image' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg'),
        ]);

    // Post should be created with the malicious string stored safely (escaped)
    $post = Post::where('user_id', $user->id)->first();
    expect($post)->not->toBeNull();
    expect($post->title)->toContain('DROP TABLE');

    // Verify posts table still exists
    expect(Post::count())->toBeGreaterThanOrEqual(1);
});

test('SQL injection in category filter is prevented', function () {
    $category = Category::first();

    // Attempt SQL injection via category route
    $response = $this->get(route('post.byCategory', ['category' => "1' OR '1'='1"]));

    // Should return 404 (model not found) rather than exposing all records
    $response->assertNotFound();
});

test('SQL injection in username route parameter is prevented', function () {
    $user = User::factory()->create(['username' => 'validuser']);

    // Attempt SQL injection via username route
    $response = $this->get("/@' OR '1'='1");

    // Should return 404, not expose data
    $response->assertNotFound();
});

test('SQL injection in post slug route parameter is prevented', function () {
    $user = User::factory()->create(['username' => 'testuser']);
    $category = Category::first();

    Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'slug' => 'valid-slug',
    ]);

    // Attempt SQL injection via post slug
    $response = $this->get("/@testuser/' OR '1'='1");

    // Should return 404
    $response->assertNotFound();
});

test('SQL injection in registration fields is prevented', function () {
    $response = $this->post(route('register'), [
        'name' => "Test'; DROP TABLE users; --",
        'username' => "testuser'; --",
        'email' => "test@example.com",
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    // User should be created with escaped strings, not execute SQL
    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toContain('DROP TABLE');

    // Verify users table still exists and has data
    expect(User::count())->toBeGreaterThanOrEqual(1);
});

test('SQL injection in profile update is prevented', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $maliciousName = "'; DELETE FROM users WHERE '1'='1";

    $response = $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => $maliciousName,
            'username' => $user->username,
            'email' => $user->email,
        ]);

    // User should be updated with the malicious string stored safely (not executed)
    $user->refresh();
    expect($user->name)->toContain('DELETE FROM');

    // Verify table still has data
    expect(User::count())->toBeGreaterThanOrEqual(1);
});

test('UNION based SQL injection is prevented', function () {
    $user = User::factory()->create();
    $category = Category::first();

    Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    // Attempt UNION injection
    $response = $this->get("/@{$user->username}/' UNION SELECT * FROM users--");

    $response->assertNotFound();
});
