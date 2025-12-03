<?php

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

    public function addDigit(string $digit): void
    {
        if (strlen($this->pin) < 6) {
            $this->pin .= $digit;
            $this->hasError = false;
            $this->errorMessage = '';

            // Auto-submit when PIN reaches expected length
            if (strlen($this->pin) >= 4) {
                $this->verifyPin();
            }
        }
    }

    public function removeDigit(): void
    {
        $this->pin = substr($this->pin, 0, -1);
        $this->hasError = false;
        $this->errorMessage = '';
    }

    public function clearPin(): void
    {
        $this->pin = '';
        $this->hasError = false;
        $this->errorMessage = '';
    }

    public function verifyPin(): void
    {
        $user = Auth::user();

        if ($user && $user->verifyPin($this->pin)) {
            $this->unlock();
        } else {
            $this->failedAttempts++;
            $this->hasError = true;
            $this->pin = '';

            if ($this->failedAttempts >= 3) {
                $this->errorMessage = 'For mange forsøk. Bruk passord.';
                $this->showPasswordFallback = true;
            } else {
                $this->errorMessage = 'Feil PIN-kode';
            }

            $this->dispatch('pin-error');
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
        $this->clearPin();
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
