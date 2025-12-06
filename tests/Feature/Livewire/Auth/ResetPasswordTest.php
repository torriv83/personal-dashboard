<?php

use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('old-password'),
    ]);
    $this->token = Password::createToken($this->user);
});

it('renders the reset password page with token', function () {
    $this->get(route('password.reset', ['token' => $this->token, 'email' => $this->user->email]))
        ->assertOk()
        ->assertSee('Tilbakestill passord');
});

it('renders the reset password component', function () {
    Livewire::test(ResetPassword::class, ['token' => $this->token])
        ->assertStatus(200);
});

it('mounts with token and email from query', function () {
    Livewire::withQueryParams(['email' => 'test@example.com'])
        ->test(ResetPassword::class, ['token' => $this->token])
        ->assertSet('token', $this->token)
        ->assertSet('email', 'test@example.com');
});

it('validates email is required', function () {
    Livewire::test(ResetPassword::class, ['token' => $this->token])
        ->set('email', '')
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('resetPassword')
        ->assertHasErrors(['email' => 'required']);
});

it('validates email is valid format', function () {
    Livewire::test(ResetPassword::class, ['token' => $this->token])
        ->set('email', 'not-an-email')
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('resetPassword')
        ->assertHasErrors(['email' => 'email']);
});

it('validates password is required', function () {
    Livewire::test(ResetPassword::class, ['token' => $this->token])
        ->set('email', 'test@example.com')
        ->set('password', '')
        ->call('resetPassword')
        ->assertHasErrors(['password' => 'required']);
});

it('validates password minimum length', function () {
    Livewire::test(ResetPassword::class, ['token' => $this->token])
        ->set('email', 'test@example.com')
        ->set('password', 'short')
        ->set('password_confirmation', 'short')
        ->call('resetPassword')
        ->assertHasErrors(['password' => 'min']);
});

it('validates password confirmation matches', function () {
    Livewire::test(ResetPassword::class, ['token' => $this->token])
        ->set('email', 'test@example.com')
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'different-password')
        ->call('resetPassword')
        ->assertHasErrors(['password' => 'confirmed']);
});

it('resets password with valid token', function () {
    Livewire::withQueryParams(['email' => 'test@example.com'])
        ->test(ResetPassword::class, ['token' => $this->token])
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    // Verify password was changed
    $this->user->refresh();
    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
});

it('fails with invalid token', function () {
    Livewire::withQueryParams(['email' => 'test@example.com'])
        ->test(ResetPassword::class, ['token' => 'invalid-token'])
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('resetPassword')
        ->assertHasErrors(['email']);
});

it('fails with wrong email', function () {
    Livewire::withQueryParams(['email' => 'wrong@example.com'])
        ->test(ResetPassword::class, ['token' => $this->token])
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('resetPassword')
        ->assertHasErrors(['email']);
});

it('flashes status message after successful reset', function () {
    Livewire::withQueryParams(['email' => 'test@example.com'])
        ->test(ResetPassword::class, ['token' => $this->token])
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('resetPassword');

    expect(session('status'))->toBe('Passordet er tilbakestilt!');
});
