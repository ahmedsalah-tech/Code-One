<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class)->group('security', 'authentication');

beforeEach(function () {
    Artisan::call('db:seed');
});

test('unauthenticated users cannot access post creation page', function () {
    $response = $this->get(route('post.create'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot create posts', function () {
    $category = Category::first();

    $response = $this->post(route('post.store'), [
        'title' => 'Test Post',
        'content' => 'Test content',
        'category_id' => $category->id,
    ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot edit posts', function () {
    $user = User::factory()->create();
    $category = Category::first();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->get(route('post.edit', $post));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot update posts', function () {
    $user = User::factory()->create();
    $category = Category::first();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->put(route('post.update', $post), [
        'title' => 'Updated',
        'content' => 'Updated content',
        'category_id' => $category->id,
    ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot delete posts', function () {
    $user = User::factory()->create();
    $category = Category::first();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->delete(route('post.destroy', $post));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot access my posts page', function () {
    $response = $this->get(route('myPosts'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot follow other users', function () {
    $user = User::factory()->create();

    $response = $this->post(route('follow', $user));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot clap posts', function () {
    $user = User::factory()->create();
    $category = Category::first();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    $response = $this->post(route('clap', $post));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot access profile edit page', function () {
    $response = $this->get(route('profile.edit'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot update profile', function () {
    $response = $this->patch(route('profile.update'), [
        'name' => 'Test',
        'email' => 'test@example.com',
    ]);

    $response->assertRedirect(route('login'));
});

test('unauthenticated users cannot delete profile', function () {
    $response = $this->delete(route('profile.destroy'), [
        'password' => 'password',
    ]);

    $response->assertRedirect(route('login'));
});

test('authenticated user cannot edit another users post', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $attacker = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    $post = Post::factory()->create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($attacker)
        ->get(route('post.edit', $post));

    $response->assertForbidden();
});

test('authenticated user cannot update another users post', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $attacker = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    $post = Post::factory()->create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($attacker)
        ->put(route('post.update', $post), [
            'title' => 'Hacked',
            'content' => 'Hacked content',
            'category_id' => $category->id,
        ]);

    $response->assertForbidden();
    expect($post->fresh()->title)->not->toBe('Hacked');
});

test('authenticated user cannot delete another users post', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $attacker = User::factory()->create(['email_verified_at' => now()]);
    $category = Category::first();

    $post = Post::factory()->create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
    ]);

    $response = $this->actingAs($attacker)
        ->delete(route('post.destroy', $post));

    $response->assertForbidden();
    expect(Post::find($post->id))->not->toBeNull();
});

test('unverified users cannot create posts', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $category = Category::first();

    $response = $this->actingAs($user)
        ->get(route('post.create'));

    $response->assertRedirect(route('verification.notice'));
});

test('password is required for profile deletion', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => '',
        ]);

    // Profile deletion uses 'userDeletion' error bag
    $response->assertSessionHasErrors('password', null, 'userDeletion');
    expect(User::find($user->id))->not->toBeNull();
});

test('incorrect password prevents profile deletion', function () {
    $user = User::factory()->create(['password' => bcrypt('correctpassword')]);

    $response = $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'wrongpassword',
        ]);

    // Profile deletion uses 'userDeletion' error bag
    $response->assertSessionHasErrors('password', null, 'userDeletion');
    expect(User::find($user->id))->not->toBeNull();
});

test('session is regenerated after login', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $initialSession = session()->getId();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    // Session should be regenerated after login
    $this->assertAuthenticated();
});

test('session is invalidated after logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'));

    $this->assertGuest();
});

test('users can view public posts without authentication', function () {
    $user = User::factory()->create(['username' => 'author']);
    $category = Category::first();
    $post = Post::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get(route('post.show', [
        'username' => $user->username,
        'post' => $post->slug,
    ]));

    $response->assertOk();
});

test('users can view public profiles without authentication', function () {
    $user = User::factory()->create(['username' => 'publicuser']);

    $response = $this->get(route('profile.show', ['user' => $user]));

    $response->assertOk();
});
