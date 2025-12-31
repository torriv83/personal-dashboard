<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;

class LockScreen extends Component
{
    public string $pin = '';

    public string $password = '';

    public int $failedAttempts = 0;

    public bool $showPasswordFallback = false;

    public bool $hasError = false;

    public string $errorMessage = '';

    public function verifyFullPin(string $pin): void
    {
        $user = Auth::user();

        if ($user && $user->verifyPin($pin)) {
            $this->unlock();
        } else {
            $this->failedAttempts++;

            if ($this->failedAttempts >= 3) {
                $this->showPasswordFallback = true;
                $this->dispatch('pin-error', message: 'For mange forsøk. Bruk passord.');
            } else {
                $this->dispatch('pin-error', message: 'Feil PIN-kode');
            }
        }
    }

    public function verifyPassword(): void
    {
        $user = Auth::user();

        if ($user && Hash::check($this->password, $user->password)) {
            $this->unlock();
        } else {
            $this->hasError = true;
            $this->errorMessage = 'Feil passord';
            $this->password = '';
        }
    }

    public function switchToPin(): void
    {
        $this->showPasswordFallback = false;
        $this->failedAttempts = 0;
        $this->pin = '';
    }

    private function unlock(): void
    {
        $this->reset(['pin', 'password', 'failedAttempts', 'showPasswordFallback', 'hasError', 'errorMessage']);
        $this->dispatch('unlocked');
    }

    #[On('lock')]
    public function lock(): void
    {
        $this->reset(['pin', 'password', 'failedAttempts', 'showPasswordFallback', 'hasError', 'errorMessage']);
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.lock-screen', [
            'userName' => $user->name ?? 'Bruker',
            'isEnabled' => $user?->isLockScreenEnabled() ?? false,
            'timeoutMinutes' => $user->lock_timeout_minutes ?? 0,
        ]);
    }
}
