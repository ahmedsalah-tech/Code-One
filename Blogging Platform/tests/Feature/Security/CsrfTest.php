<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)->group('security', 'csrf');

beforeEach(function () {
    Artisan::call('db:seed');
});

test('CSRF middleware is enabled in application', function () {
    // Verify CSRF protection is active by checking session has token
    $response = $this->get('/');

    expect(session()->token())->not->toBeEmpty();
});

test('forms include CSRF token field', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('post.create'));

    $response->assertOk();
    $response->assertSee('name="_token"', false);
});

test('login form includes CSRF token', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('name="_token"', false);
});

test('registration form includes CSRF token', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
    $response->assertSee('name="_token"', false);
});

test('post edit form includes CSRF token', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)->get(route('post.edit', $post));

    $response->assertOk();
    $response->assertSee('name="_token"', false);
});

test('profile edit form includes CSRF token', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertOk();
    $response->assertSee('name="_token"', false);
});

test('delete forms include CSRF token and method spoofing', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($user)->get(route('post.show', [
        'username' => $user->username,
        'post' => $post->slug,
    ]));

    $response->assertOk();
    $response->assertSee('name="_token"', false);
    $response->assertSee('name="_method"', false);
});

test('CSRF token endpoint returns valid token', function () {
    $response = $this->get(route('csrf.token'));

    $response->assertOk();
    $response->assertJsonStructure(['token']);
    expect($response->json('token'))->not->toBeEmpty();
});

test('CSRF token is unique per session', function () {
    $response1 = $this->get(route('csrf.token'));
    $token1 = $response1->json('token');

    // Same session should get same token
    $response2 = $this->get(route('csrf.token'));
    $token2 = $response2->json('token');

    expect($token1)->toBe($token2);
});

test('valid CSRF token allows form submission', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    // With valid CSRF token (provided automatically by test helper)
    $response = $this->actingAs($user)
        ->post(route('post.store'), [
            'title' => 'Test Post',
            'content' => 'Test content',
            'category_id' => $category->id,
            'image' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg'),
        ]);

    // Should succeed (redirect to dashboard)
    $response->assertRedirect();
    expect(Post::where('user_id', $user->id)->exists())->toBeTrue();
});
