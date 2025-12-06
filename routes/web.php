<?php

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Bpa\Assistants;
use App\Livewire\Bpa\AssistantShow;
use App\Livewire\Bpa\Calendar;
use App\Livewire\Bpa\Dashboard as BpaDashboard;
use App\Livewire\Bpa\Timesheets;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Economy\Index as EconomyIndex;
use App\Livewire\Medical\Dashboard as MedicalDashboard;
use App\Livewire\Medical\Equipment;
use App\Livewire\Medical\Prescriptions;
use App\Livewire\Medical\Weight;
use App\Livewire\Tools\PortGenerator;
use App\Livewire\User\Profile;
use App\Livewire\User\Settings;
use App\Livewire\Wishlist\Index as WishlistIndex;
use Illuminate\Support\Facades\Route;

// Auth (gjester - redirect til dashboard hvis allerede innlogget)
Route::middleware('guest')->group(function () {
    Route::get('/logg-inn', Login::class)->name('login');
    Route::get('/glemt-passord', ForgotPassword::class)->name('password.request');
    Route::get('/tilbakestill-passord/{token}', ResetPassword::class)->name('password.reset');
});

// Beskyttede ruter (krever innlogging)
Route::middleware('auth')->group(function () {
    Route::get('/', DashboardIndex::class)->name('dashboard');

    // Bruker
    Route::get('/profil', Profile::class)->name('profile');
    Route::get('/innstillinger', Settings::class)->name('settings');
    Route::get('/innstillinger/backup', \App\Livewire\User\Backup::class)->name('settings.backup');

    // BPA (Brukerstyrt personlig assistanse)
    Route::prefix('bpa')->name('bpa.')->group(function () {
        Route::get('/', BpaDashboard::class)->name('dashboard');
        Route::get('/kalender', Calendar::class)->name('calendar');
        Route::get('/timelister', Timesheets::class)->name('timesheets');
        Route::get('/assistenter', Assistants::class)->name('assistants');
        Route::get('/assistenter/{assistant}', AssistantShow::class)->name('assistants.show');
    });

    // Medisinsk
    Route::prefix('medisinsk')->name('medical.')->group(function () {
        Route::get('/', MedicalDashboard::class)->name('dashboard');
        Route::get('/utstyr', Equipment::class)->name('equipment');
        Route::get('/resepter', Prescriptions::class)->name('prescriptions');
        Route::get('/vekt', Weight::class)->name('weight');
    });

    // Økonomi
    Route::get('/okonomi', EconomyIndex::class)->name('economy');

    // Ønskeliste
    Route::get('/onskeliste', WishlistIndex::class)->name('wishlist');

    // Verktøy
    Route::prefix('verktoy')->name('tools.')->group(function () {
        Route::get('/port-generator', PortGenerator::class)->name('port-generator');
    });
});
