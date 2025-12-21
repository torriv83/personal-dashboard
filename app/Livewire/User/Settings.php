<?php

namespace App\Livewire\User;

use App\Models\Setting;
use App\Services\WeatherService;
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

    public float $bpaHourlyRate = 225.40;

    public string $weatherLocationSearch = '';

    public string $weatherLocationName = 'Halden';

    public string $weatherLatitude = '59.1229';

    public string $weatherLongitude = '11.3875';

    public bool $weatherEnabled = true;

    // Push notification settings
    public bool $pushSubscribed = false;

    public bool $pushPrescriptionEnabled = false;

    public string $pushPrescriptionTime = '09:00';

    public bool $pushShiftEnabled = false;

    public bool $pushShiftDayBefore = true;

    public ?int $pushShiftHoursBefore = 2;

    // Mileage calculator settings
    public string $mileageHomeAddress = '';

    // Bookmarklet settings
    public bool $showBookmarkToken = false;

    public string $bookmarkToken = '';

    public function mount(): void
    {
        $this->lockTimeoutMinutes = Auth::user()->lock_timeout_minutes ?? 30;
        $this->bpaHoursPerWeek = Setting::getBpaHoursPerWeek();
        $this->bpaHourlyRate = Setting::getBpaHourlyRate();

        $this->weatherEnabled = (bool) Setting::get('weather_enabled', true);
        $this->weatherLocationName = Setting::get('weather_location_name', 'Halden');
        $this->weatherLatitude = (string) Setting::get('weather_latitude', '59.1229');
        $this->weatherLongitude = (string) Setting::get('weather_longitude', '11.3875');
        $this->weatherLocationSearch = $this->weatherLocationName;

        // Push notification settings
        $this->pushSubscribed = Auth::user()->pushSubscriptions()->exists();
        $this->pushPrescriptionEnabled = (bool) Setting::get('push_prescription_enabled', false);
        $this->pushPrescriptionTime = Setting::get('push_prescription_time', '09:00');
        $this->pushShiftEnabled = (bool) Setting::get('push_shift_enabled', false);
        $this->pushShiftDayBefore = (bool) Setting::get('push_shift_day_before', true);
        $this->pushShiftHoursBefore = Setting::get('push_shift_hours_before') ? (int) Setting::get('push_shift_hours_before') : 2;

        // Mileage calculator
        $this->mileageHomeAddress = Setting::get('mileage_home_address', '');

        // Bookmarklet
        $this->bookmarkToken = Auth::user()->ensureBookmarkToken();
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

    public function saveBpaHourlyRate(): void
    {
        $this->validate([
            'bpaHourlyRate' => ['required', 'numeric', 'min:0', 'max:1000'],
        ], [
            'bpaHourlyRate.required' => 'Timesats er påkrevd.',
            'bpaHourlyRate.numeric' => 'Timesats må være et tall.',
            'bpaHourlyRate.min' => 'Timesats kan ikke være negativt.',
            'bpaHourlyRate.max' => 'Timesats kan maks være 1000.',
        ]);

        Setting::setBpaHourlyRate($this->bpaHourlyRate);

        $this->dispatch('hourly-rate-saved');
    }

    public function searchWeatherLocation(): void
    {
        $this->validate([
            'weatherLocationSearch' => ['required', 'string', 'min:2', 'max:100'],
        ], [
            'weatherLocationSearch.required' => 'Skriv inn et stedsnavn.',
            'weatherLocationSearch.min' => 'Stedsnavn må være minst 2 tegn.',
        ]);

        $result = app(WeatherService::class)->searchLocation($this->weatherLocationSearch);

        if (! $result) {
            $this->addError('weatherLocationSearch', 'Fant ikke stedet. Prøv et annet søk.');

            return;
        }

        $this->weatherLocationName = $result['name'];
        $this->weatherLatitude = (string) $result['lat'];
        $this->weatherLongitude = (string) $result['lon'];
        $this->weatherLocationSearch = $result['name'];

        Setting::set('weather_location_name', $this->weatherLocationName);
        Setting::set('weather_latitude', $this->weatherLatitude);
        Setting::set('weather_longitude', $this->weatherLongitude);

        app(WeatherService::class)->clearCache();

        $this->dispatch('weather-saved');
    }

    public function toggleWeather(): void
    {
        $this->weatherEnabled = ! $this->weatherEnabled;
        Setting::set('weather_enabled', $this->weatherEnabled);
        $this->dispatch('weather-toggled');
    }

    public function savePushSubscription(string $endpoint, ?string $publicKey, ?string $authToken): void
    {
        Auth::user()->updatePushSubscription($endpoint, $publicKey, $authToken);
        $this->pushSubscribed = true;
        $this->dispatch('push-subscribed');
    }

    public function removePushSubscription(string $endpoint): void
    {
        Auth::user()->deletePushSubscription($endpoint);
        $this->pushSubscribed = Auth::user()->pushSubscriptions()->exists();
        $this->dispatch('push-unsubscribed');
    }

    public function togglePrescriptionAlerts(): void
    {
        $this->pushPrescriptionEnabled = ! $this->pushPrescriptionEnabled;
        Setting::set('push_prescription_enabled', $this->pushPrescriptionEnabled);

        // Save default time when enabling
        if ($this->pushPrescriptionEnabled && ! Setting::get('push_prescription_time')) {
            Setting::set('push_prescription_time', $this->pushPrescriptionTime);
        }

        $this->dispatch('prescription-alerts-toggled');
    }

    public function savePrescriptionTime(): void
    {
        $this->validate([
            'pushPrescriptionTime' => ['required', 'date_format:H:i'],
        ]);

        Setting::set('push_prescription_time', $this->pushPrescriptionTime);
        $this->dispatch('prescription-time-saved');
    }

    public function toggleShiftReminders(): void
    {
        $this->pushShiftEnabled = ! $this->pushShiftEnabled;
        Setting::set('push_shift_enabled', $this->pushShiftEnabled);

        // Save default values when enabling
        if ($this->pushShiftEnabled) {
            if (Setting::get('push_shift_day_before') === null) {
                Setting::set('push_shift_day_before', $this->pushShiftDayBefore);
            }
            if (Setting::get('push_shift_hours_before') === null) {
                Setting::set('push_shift_hours_before', $this->pushShiftHoursBefore);
            }
        }

        $this->dispatch('shift-reminders-toggled');
    }

    public function toggleShiftDayBefore(): void
    {
        $this->pushShiftDayBefore = ! $this->pushShiftDayBefore;
        Setting::set('push_shift_day_before', $this->pushShiftDayBefore);
        $this->dispatch('shift-day-before-toggled');
    }

    public function saveShiftHoursBefore(): void
    {
        Setting::set('push_shift_hours_before', $this->pushShiftHoursBefore);
        $this->dispatch('shift-hours-saved');
    }

    public function saveMileageHomeAddress(): void
    {
        $this->validate([
            'mileageHomeAddress' => ['required', 'string', 'max:255'],
        ], [
            'mileageHomeAddress.required' => 'Hjemmeadressen er påkrevd.',
            'mileageHomeAddress.max' => 'Hjemmeadressen kan ikke være lengre enn 255 tegn.',
        ]);

        Setting::set('mileage_home_address', $this->mileageHomeAddress);

        $this->dispatch('mileage-home-saved');
    }

    public function toggleBookmarkToken(): void
    {
        $this->showBookmarkToken = ! $this->showBookmarkToken;
    }

    public function regenerateBookmarkToken(): void
    {
        $this->bookmarkToken = Auth::user()->regenerateBookmarkToken();
        $this->dispatch('toast', type: 'success', message: 'Token regenerert!');
    }

    public function getBookmarkletUrl(): string
    {
        return route('tools.bookmarks.add', ['token' => $this->bookmarkToken]);
    }

    public function getBookmarkletCode(): string
    {
        $url = $this->getBookmarkletUrl();

        return "javascript:(function(){window.open('{$url}&url='+encodeURIComponent(location.href)+'&title='+encodeURIComponent(document.title))})();";
    }

    public function render()
    {
        return view('livewire.user.settings', [
            'hasPin' => Auth::user()->hasPin(),
            'vapidPublicKey' => config('webpush.vapid.public_key'),
        ]);
    }
}
