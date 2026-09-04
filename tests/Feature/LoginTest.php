<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('creates a token when valid username and password are provided', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $response = $this->postJson('/api/v1/tokens', [
        'username' => $user->username,
        'password' => 'password123',
    ]);

    $response->assertCreated()->assertJsonStructure(['token']);

    expect($user->tokens()->count())->toBe(1);
});

it('creates a token when valid email and password are provided', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $response = $this->postJson('/api/v1/tokens', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertCreated()->assertJsonStructure(['token']);

    expect($user->tokens()->count())->toBe(1);
});


it('fails when both username and email are missing', function () {
    $this->postJson('/api/v1/tokens', ['password' => 'password123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['username', 'email']);
});

it('fails when password is missing', function () {
    $this->postJson('/api/v1/tokens', ['username' => 'john'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('fails with invalid username and password', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/v1/tokens', [
        'username' => $user->username,
        'password' => 'wrong-password',
    ])->assertStatus(401);
});

it('fails with invalid email and password', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/v1/tokens', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(401);
});

it('fails with non-existent username', function () {
    $this->postJson('/api/v1/tokens', [
        'username' => 'ghost',
        'password' => 'password123',
    ])->assertStatus(401);
});

it('fails with non-existent email', function () {
    $this->postJson('/api/v1/tokens', [
        'email' => 'ghost@example.com',
        'password' => 'password123',
    ])->assertStatus(401);
});
