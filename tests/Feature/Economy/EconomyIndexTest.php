<?php

use App\Livewire\Economy\Index;
use App\Models\IncomeSetting;
use App\Models\User;
use App\Services\YnabService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    IncomeSetting::query()->delete();
    Cache::flush();
});

test('economy page renders successfully', function () {
    mockYnabService();

    $this->get('/okonomi')
        ->assertOk()
        ->assertSee('Økonomi');
});

test('economy page shows income settings', function () {
    mockYnabService();

    IncomeSetting::create([
        'monthly_gross' => 50000,
        'monthly_net' => 35000,
        'tax_table' => '7100',
        'base_support' => 3000,
    ]);

    Livewire::test(Index::class)
        ->assertSet('monthly_gross', '50000')
        ->assertSet('monthly_net', '35000')
        ->assertSet('tax_table', '7100')
        ->assertSet('base_support', '3000');
});

test('income settings can be saved', function () {
    mockYnabService();

    Livewire::test(Index::class)
        ->set('monthly_gross', '55000')
        ->set('monthly_net', '38000')
        ->set('tax_table', '7101')
        ->set('base_support', '3500')
        ->call('saveIncomeSettings')
        ->assertDispatched('close-modal', name: 'income-settings');

    $setting = IncomeSetting::first();

    expect((float) $setting->monthly_gross)->toBe(55000.0);
    expect((float) $setting->monthly_net)->toBe(38000.0);
    expect($setting->tax_table)->toBe('7101');
    expect((float) $setting->base_support)->toBe(3500.0);
});

test('income settings validation requires monthly_gross', function () {
    mockYnabService();

    Livewire::test(Index::class)
        ->set('monthly_gross', '')
        ->set('monthly_net', '35000')
        ->set('base_support', '3000')
        ->call('saveIncomeSettings')
        ->assertHasErrors(['monthly_gross' => 'required']);
});

test('income settings validation requires monthly_net', function () {
    mockYnabService();

    Livewire::test(Index::class)
        ->set('monthly_gross', '50000')
        ->set('monthly_net', '')
        ->set('base_support', '3000')
        ->call('saveIncomeSettings')
        ->assertHasErrors(['monthly_net' => 'required']);
});

test('income settings validation requires base_support', function () {
    mockYnabService();

    Livewire::test(Index::class)
        ->set('monthly_gross', '50000')
        ->set('monthly_net', '35000')
        ->set('base_support', '')
        ->call('saveIncomeSettings')
        ->assertHasErrors(['base_support' => 'required']);
});

test('income settings validation requires positive numbers', function () {
    mockYnabService();

    Livewire::test(Index::class)
        ->set('monthly_gross', '-1000')
        ->set('monthly_net', '-500')
        ->set('base_support', '-100')
        ->call('saveIncomeSettings')
        ->assertHasErrors(['monthly_gross', 'monthly_net', 'base_support']);
});

test('syncYnab dispatches event and stores sync timestamp', function () {
    mockYnabService();

    expect(Cache::has('ynab.last_synced'))->toBeFalse();

    Livewire::test(Index::class)
        ->call('syncYnab')
        ->assertDispatched('syncCompleted');

    // Verify sync timestamp was stored
    expect(Cache::has('ynab.last_synced'))->toBeTrue();
});

test('totalBalance computes sum of account balances', function () {
    mockYnabService([
        'accounts' => [
            ['id' => '1', 'name' => 'Checking', 'type' => 'checking', 'balance' => 1000.0, 'cleared_balance' => 1000.0, 'last_reconciled_at' => null],
            ['id' => '2', 'name' => 'Savings', 'type' => 'savings', 'balance' => 5000.0, 'cleared_balance' => 5000.0, 'last_reconciled_at' => null],
        ],
    ]);

    Livewire::test(Index::class)
        ->assertSet('totalBalance', 6000.0);
});

test('isYnabConfigured returns true when configured', function () {
    mockYnabService(['isConfigured' => true]);

    Livewire::test(Index::class)
        ->assertSet('isYnabConfigured', true);
});

test('isYnabConfigured returns false when not configured', function () {
    mockYnabService(['isConfigured' => false]);

    Livewire::test(Index::class)
        ->assertSet('isYnabConfigured', false);
});

test('tax_table can be nullable', function () {
    mockYnabService();

    Livewire::test(Index::class)
        ->set('monthly_gross', '50000')
        ->set('monthly_net', '35000')
        ->set('tax_table', '')
        ->set('base_support', '3000')
        ->call('saveIncomeSettings')
        ->assertHasNoErrors();

    $setting = IncomeSetting::first();
    expect($setting->tax_table)->toBeNull();
});

test('lastSyncedAt shows formatted sync time', function () {
    mockYnabService();

    Cache::put('ynab.last_synced', now()->setDate(2024, 12, 4)->setTime(15, 30), 86400);

    Livewire::test(Index::class)
        ->assertSet('lastSyncedAt', '04.12.2024 kl. 15:30');
});

test('lastSyncedAt returns null when never synced', function () {
    mockYnabService();

    Livewire::test(Index::class)
        ->assertSet('lastSyncedAt', null);
});

test('ynabErrors displays when API returns errors', function () {
    mockYnabService([
        'errors' => ['kontoer' => 'Kunne ikke koble til YNAB'],
    ]);

    Livewire::test(Index::class)
        ->call('loadYnabData')
        ->assertSet('ynabErrors', ['kontoer' => 'Kunne ikke koble til YNAB'])
        ->assertSee('Kunne ikke hente all data fra YNAB');
});

test('syncYnab shows error toast when errors occur', function () {
    mockYnabService([
        'errors' => ['kontoer' => 'YNAB-serveren er utilgjengelig'],
    ]);

    Livewire::test(Index::class)
        ->call('syncYnab')
        ->assertDispatched('toast', type: 'error', message: 'Noen data kunne ikke hentes fra YNAB');
});

/**
 * Helper function to mock YnabService.
 */
function mockYnabService(array $overrides = []): void
{
    $defaults = [
        'isConfigured' => true,
        'accounts' => [],
        'ageOfMoney' => 42,
        'monthlyData' => [],
        'budgetDetails' => ['name' => 'Test Budget', 'last_modified_on' => '2024-12-04T15:30:00Z'],
        'errors' => [],
    ];

    $data = array_merge($defaults, $overrides);

    $mock = Mockery::mock(YnabService::class);
    $mock->shouldReceive('isConfigured')->andReturn($data['isConfigured']);
    $mock->shouldReceive('getAccounts')->andReturnUsing(function () use ($data) {
        Cache::forever('ynab.last_synced', now());

        return $data['accounts'];
    });
    $mock->shouldReceive('getAgeOfMoney')->andReturn($data['ageOfMoney']);
    $mock->shouldReceive('getMonthlyData')->andReturn($data['monthlyData']);
    $mock->shouldReceive('getBudgetDetails')->andReturn($data['budgetDetails']);
    $mock->shouldReceive('clearCache')->andReturnNull();
    $mock->shouldReceive('clearErrors')->andReturnNull();
    $mock->shouldReceive('getErrors')->andReturn($data['errors']);

    app()->instance(YnabService::class, $mock);
}
