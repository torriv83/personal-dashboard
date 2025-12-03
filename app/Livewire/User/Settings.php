<?php

namespace App\Livewire\User;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Settings extends Component
{
    public string $newPin = '';

    public string $confirmPin = '';

    public int $lockTimeoutMinutes = 30;

    public string $currentPassword = '';

    public bool $showPinModal = false;

    public bool $showRemovePinModal = false;

    public float $bpaHoursPerWeek = 0;

    public function mount(): void
    {
        $this->lockTimeoutMinutes = Auth::user()->lock_timeout_minutes ?? 30;
        $this->bpaHoursPerWeek = Setting::getBpaHoursPerWeek();
    }

    public function openPinModal(): void
    {
        $this->reset(['newPin', 'confirmPin', 'currentPassword']);
        $this->showPinModal = true;
    }

    public function closePinModal(): void
    {
        $this->showPinModal = false;
        $this->reset(['newPin', 'confirmPin', 'currentPassword']);
    }

    public function savePin(): void
    {
        $this->validate([
            'newPin' => ['required', 'string', 'min:4', 'max:6', 'regex:/^[0-9]+$/'],
            'confirmPin' => ['required', 'same:newPin'],
            'currentPassword' => ['required', 'current_password'],
        ], [
            'newPin.required' => 'PIN-kode er påkrevd.',
            'newPin.min' => 'PIN-kode må være minst 4 siffer.',
            'newPin.max' => 'PIN-kode kan maks være 6 siffer.',
            'newPin.regex' => 'PIN-kode kan kun inneholde tall.',
            'confirmPin.required' => 'Bekreft PIN-kode.',
            'confirmPin.same' => 'PIN-kodene stemmer ikke overens.',
            'currentPassword.required' => 'Passord er påkrevd.',
            'currentPassword.current_password' => 'Feil passord.',
        ]);

        Auth::user()->setPin($this->newPin);

        $this->closePinModal();
        $this->dispatch('notify', message: 'PIN-kode lagret!', type: 'success');
    }

    public function openRemovePinModal(): void
    {
        $this->reset('currentPassword');
        $this->showRemovePinModal = true;
    }

    public function closeRemovePinModal(): void
    {
        $this->showRemovePinModal = false;
        $this->reset('currentPassword');
    }

    public function removePin(): void
    {
        $this->validate([
            'currentPassword' => ['required', 'current_password'],
        ], [
            'currentPassword.required' => 'Passord er påkrevd.',
            'currentPassword.current_password' => 'Feil passord.',
        ]);

        $user = Auth::user();
        $user->pin = null;
        $user->save();

        $this->closeRemovePinModal();
        $this->dispatch('notify', message: 'PIN-kode fjernet.', type: 'success');
    }

    public function updateLockTimeout(): void
    {
        $this->validate([
            'lockTimeoutMinutes' => ['required', 'integer', 'min:0', 'max:480'],
        ]);

        $user = Auth::user();
        $user->lock_timeout_minutes = $this->lockTimeoutMinutes;
        $user->save();

        $this->dispatch('timeout-saved');
    }

    public function saveBpaHoursPerWeek(): void
    {
        $this->validate([
            'bpaHoursPerWeek' => ['required', 'numeric', 'min:0', 'max:168'],
        ], [
            'bpaHoursPerWeek.required' => 'Timer per uke er påkrevd.',
            'bpaHoursPerWeek.numeric' => 'Timer per uke må være et tall.',
            'bpaHoursPerWeek.min' => 'Timer per uke kan ikke være negativt.',
            'bpaHoursPerWeek.max' => 'Timer per uke kan maks være 168.',
        ]);

        Setting::setBpaHoursPerWeek($this->bpaHoursPerWeek);

        $this->dispatch('bpa-saved');
    }

    public function render()
    {
        return view('livewire.user.settings', [
            'hasPin' => Auth::user()->hasPin(),
        ]);
    }
}
