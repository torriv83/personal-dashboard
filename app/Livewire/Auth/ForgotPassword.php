<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Glemt passord')]
class ForgotPassword extends Component
{
    public string $email = '';

    public bool $submitted = false;

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'E-post er påkrevd.',
            'email.email' => 'Ugyldig e-postadresse.',
        ]);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->submitted = true;
        } else {
            $this->addError('email', 'Kunne ikke sende tilbakestillingslenke.');
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
