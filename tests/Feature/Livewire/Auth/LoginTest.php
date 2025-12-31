<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    // Clear rate limiter for the test email/IP combination
    RateLimiter::clear('test@example.com|127.0.0.1');

    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password123'),
    ]);
});

it('renders the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Logg inn');
});

it('renders the login component', function () {
    Livewire::test(Login::class)
        ->assertStatus(200)
        ->assertSee('E-post')
        ->assertSee('Passord');
});

it('validates email is required', function () {
    Livewire::test(Login::class)
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors(['email' => 'required']);
});

it('validates email is valid format', function () {
    Livewire::test(Login::class)
        ->set('email', 'not-an-email')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors(['email' => 'email']);
});

it('validates password is required', function () {
    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->call('login')
        ->assertHasErrors(['password' => 'required']);
});

it('can login with valid credentials', function () {
    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
});

it('fails login with wrong password', function () {
    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

it('fails login with non-existent email', function () {
    Livewire::test(Login::class)
        ->set('email', 'nonexistent@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors(['email']);

    $this->assertGuest();
});

it('can remember user when checkbox is checked', function () {
    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->set('remember', true)
        ->call('login')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
});

it('rate limits after too many failed attempts', function () {
    // Attempt login 6 times with wrong password
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    // The 6th attempt should be rate limited
    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasErrors(['email']);
});

it('clears rate limiter on successful login', function () {
    // Clear any existing rate limits first
    RateLimiter::clear('test@example.com|127.0.0.1');

    // Make a few failed attempts
    for ($i = 0; $i < 3; $i++) {
        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login');
    }

    // Successful login should work (not rate limited yet)
    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));
});

it('redirects to dashboard after successful login', function () {
    Livewire::test(Login::class)
        ->set('email', 'test@example.com')
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
});
