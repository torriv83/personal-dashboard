<?php

declare(strict_types=1);

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Bookmarks\Index as BookmarksIndex;
use App\Livewire\Bookmarks\QuickAdd as BookmarksQuickAdd;
use App\Livewire\Bpa\Assistants;
use App\Livewire\Bpa\AssistantShow;
use App\Livewire\Bpa\Calendar;
use App\Livewire\Bpa\Dashboard as BpaDashboard;
use App\Livewire\Bpa\Timesheets;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Economy\Index as EconomyIndex;
use App\Livewire\Games\Rommers;
use App\Livewire\Medical\Dashboard as MedicalDashboard;
use App\Livewire\Medical\Equipment;
use App\Livewire\Medical\Hjelpemidler;
use App\Livewire\Medical\Prescriptions;
use App\Livewire\Medical\Weight;
use App\Livewire\Tasks\AssistantTasks;
use App\Livewire\Tasks\Index as TasksIndex;
use App\Livewire\Tasks\Show as TasksShow;
use App\Livewire\Tools\MileageCalculator;
use App\Livewire\Tools\PortGenerator;
use App\Livewire\User\Profile;
use App\Livewire\User\Settings;
use App\Livewire\Wishlist\Index as WishlistIndex;
use App\Livewire\Wishlist\SharedView as WishlistSharedView;
use Illuminate\Support\Facades\Route;

// Offentlige ruter (ingen innlogging krevd)
Route::get('/delt/{token}', WishlistSharedView::class)->name('wishlist.shared');
Route::get('/oppgaver/{assistant:token}', AssistantTasks::class)->name('tasks.assistant');
Route::get('/oppgaver/{assistant:token}/manifest.json', [\App\Http\Controllers\AssistantPwaController::class, 'manifest'])->name('tasks.assistant.manifest');
Route::get('/oppgaver/{assistant:token}/sw.js', [\App\Http\Controllers\AssistantPwaController::class, 'serviceWorker'])->name('tasks.assistant.sw');
Route::get('/verktoy/bokmerker/legg-til', BookmarksQuickAdd::class)->name('tools.bookmarks.add');

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
    // BPA (Brukerstyrt personlig assistanse)
    Route::prefix('bpa')->name('bpa.')->group(function () {
        Route::get('/', BpaDashboard::class)->name('dashboard');
        Route::get('/kalender', Calendar::class)->name('calendar');
        Route::get('/timelister', Timesheets::class)->name('timesheets');
        Route::get('/assistenter', Assistants::class)->name('assistants');
        Route::get('/assistenter/{assistant}', AssistantShow::class)->name('assistants.show');

        // Oppgavelister
        Route::get('/oppgaver', TasksIndex::class)->name('tasks.index');
        Route::get('/oppgaver/{taskList:slug}', TasksShow::class)->name('tasks.show');
    });

    // Medisinsk
    Route::prefix('medisinsk')->name('medical.')->group(function () {
        Route::get('/', MedicalDashboard::class)->name('dashboard');
        Route::get('/utstyr', Equipment::class)->name('equipment');
        Route::get('/resepter', Prescriptions::class)->name('prescriptions');
        Route::get('/vekt', Weight::class)->name('weight');
        Route::get('/hjelpemidler', Hjelpemidler::class)->name('hjelpemidler');
    });

    // Økonomi
    Route::get('/okonomi', EconomyIndex::class)->name('economy');

    // Ønskeliste
    Route::get('/onskeliste', WishlistIndex::class)->name('wishlist');

    // Spill
    Route::prefix('spill')->name('games.')->group(function () {
        Route::get('/rommers', Rommers::class)->name('rommers');
    });

    // Verktøy
    Route::prefix('verktoy')->name('tools.')->group(function () {
        Route::get('/bokmerker', BookmarksIndex::class)->name('bookmarks');
        Route::get('/port-generator', PortGenerator::class)->name('port-generator');
        Route::get('/kjoregodtgjorelse', MileageCalculator::class)->name('mileage-calculator');
    });
});
