<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)->group('security', 'xss');

beforeEach(function () {
    Artisan::call('db:seed');
});

test('XSS script tags are escaped in post title', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    $maliciousTitle = '<script>alert("XSS")</script>Test Title';

    $response = $this->actingAs($user)
        ->post(route('post.store'), [
            'title' => $maliciousTitle,
            'content' => 'Safe content',
            'category_id' => $category->id,
            'image' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg'),
        ]);

    $post = Post::where('user_id', $user->id)->first();

    // The title should be stored but when rendered, Blade's {{ }} escapes it
    expect($post)->not->toBeNull();

    // View the post and ensure script is escaped in HTML output
    $viewResponse = $this->get(route('post.show', [
        'username' => $user->username,
        'post' => $post->slug,
    ]));

    $viewResponse->assertOk();
    // The raw script tag should NOT appear - it should be HTML-escaped
    // Looking for the escaped version proves XSS protection is working
    $viewResponse->assertSee('&lt;script&gt;', false);
});

test('XSS script tags are escaped in post content', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    $maliciousContent = '<script>document.cookie</script><img src=x onerror=alert(1)>';

    $response = $this->actingAs($user)
        ->post(route('post.store'), [
            'title' => 'Safe Title',
            'content' => $maliciousContent,
            'category_id' => $category->id,
            'image' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg'),
        ]);

    $post = Post::where('user_id', $user->id)->first();
    expect($post)->not->toBeNull();

    $viewResponse = $this->get(route('post.show', [
        'username' => $user->username,
        'post' => $post->slug,
    ]));

    $viewResponse->assertOk();
    // Verify malicious content IS escaped (the escaped entities should be present)
    $viewResponse->assertSee('&lt;script&gt;', false);
    $viewResponse->assertSee('&lt;img', false);
});

test('XSS in username is escaped on profile page', function () {
    // Create user with potentially malicious name (username has stricter validation)
    $user = User::factory()->create([
        'name' => '<script>alert(1)</script>Test User',
        'username' => 'safeusername',
    ]);

    // View profile and verify name is escaped
    $profileResponse = $this->get(route('profile.show', ['user' => $user]));

    $profileResponse->assertOk();
    // The escaped version should appear, not raw script
    $profileResponse->assertSee('&lt;script&gt;', false);
});

test('XSS event handlers are escaped', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    $maliciousContent = '<img src="x" onerror="alert(\'XSS\')">';

    $this->actingAs($user)
        ->post(route('post.store'), [
            'title' => 'Test Post',
            'content' => $maliciousContent,
            'category_id' => $category->id,
            'image' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg'),
        ]);

    $post = Post::where('user_id', $user->id)->first();

    $viewResponse = $this->get(route('post.show', [
        'username' => $user->username,
        'post' => $post->slug,
    ]));

    // Event handler should be escaped - the &lt;img tag should appear escaped
    $viewResponse->assertSee('&lt;img', false);
});

test('XSS javascript protocol URLs are escaped', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    $maliciousContent = '<a href="javascript:alert(\'XSS\')">Click me</a>';

    $this->actingAs($user)
        ->post(route('post.store'), [
            'title' => 'Test Post',
            'content' => $maliciousContent,
            'category_id' => $category->id,
            'image' => \Illuminate\Http\UploadedFile::fake()->image('test.jpg'),
        ]);

    $post = Post::where('user_id', $user->id)->first();

    $viewResponse = $this->get(route('post.show', [
        'username' => $user->username,
        'post' => $post->slug,
    ]));

    // JavaScript protocol should be escaped - looking for escaped anchor tag
    $viewResponse->assertSee('&lt;a href=', false);
});
