<?php

use App\Services\YnabService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
});

test('isConfigured returns true when token and budget_id are set', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
    ]);

    $service = new YnabService;

    expect($service->isConfigured())->toBeTrue();
});

test('isConfigured returns false when token is missing', function () {
    config([
        'services.ynab.token' => '',
        'services.ynab.budget_id' => 'test-budget-id',
    ]);

    $service = new YnabService;

    expect($service->isConfigured())->toBeFalse();
});

test('isConfigured returns false when budget_id is missing', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => '',
    ]);

    $service = new YnabService;

    expect($service->isConfigured())->toBeFalse();
});

test('getAccounts returns empty array when not configured', function () {
    config([
        'services.ynab.token' => '',
        'services.ynab.budget_id' => '',
    ]);

    $service = new YnabService;

    expect($service->getAccounts())->toBe([]);
});

test('getAccounts returns formatted accounts from API', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
        'services.ynab.base_url' => 'https://api.ynab.com/v1',
    ]);

    Http::fake([
        'api.ynab.com/v1/budgets/test-budget-id/accounts' => Http::response([
            'data' => [
                'accounts' => [
                    [
                        'id' => 'acc-1',
                        'name' => 'Checking',
                        'type' => 'checking',
                        'balance' => 150000, // 150.00 in milliunits
                        'cleared_balance' => 145000,
                        'last_reconciled_at' => '2024-12-01T10:00:00Z',
                        'deleted' => false,
                        'closed' => false,
                    ],
                    [
                        'id' => 'acc-2',
                        'name' => 'Savings',
                        'type' => 'savings',
                        'balance' => 500000, // 500.00 in milliunits
                        'cleared_balance' => 500000,
                        'last_reconciled_at' => null,
                        'deleted' => false,
                        'closed' => false,
                    ],
                    [
                        'id' => 'acc-deleted',
                        'name' => 'Deleted Account',
                        'type' => 'checking',
                        'balance' => 0,
                        'cleared_balance' => 0,
                        'last_reconciled_at' => null,
                        'deleted' => true,
                        'closed' => false,
                    ],
                    [
                        'id' => 'acc-closed',
                        'name' => 'Closed Account',
                        'type' => 'checking',
                        'balance' => 0,
                        'cleared_balance' => 0,
                        'last_reconciled_at' => null,
                        'deleted' => false,
                        'closed' => true,
                    ],
                ],
            ],
        ]),
    ]);

    $service = new YnabService;
    $accounts = $service->getAccounts();

    expect($accounts)->toHaveCount(2);
    expect($accounts[0]['name'])->toBe('Checking');
    expect($accounts[0]['balance'])->toEqual(150.0);
    expect($accounts[0]['cleared_balance'])->toEqual(145.0);
    expect($accounts[1]['name'])->toBe('Savings');
    expect($accounts[1]['balance'])->toEqual(500.0);
});

test('getAccounts caches the result', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
        'services.ynab.base_url' => 'https://api.ynab.com/v1',
    ]);

    Http::fake([
        'api.ynab.com/v1/budgets/test-budget-id/accounts' => Http::response([
            'data' => [
                'accounts' => [
                    [
                        'id' => 'acc-1',
                        'name' => 'Checking',
                        'type' => 'checking',
                        'balance' => 150000,
                        'cleared_balance' => 145000,
                        'last_reconciled_at' => null,
                        'deleted' => false,
                        'closed' => false,
                    ],
                ],
            ],
        ]),
    ]);

    $service = new YnabService;

    // First call should hit the API
    $service->getAccounts();

    // Second call should use cache
    $service->getAccounts();

    Http::assertSentCount(1);
});

test('getBudgetDetails returns budget info', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
        'services.ynab.base_url' => 'https://api.ynab.com/v1',
    ]);

    Http::fake([
        'api.ynab.com/v1/budgets/test-budget-id' => Http::response([
            'data' => [
                'budget' => [
                    'name' => 'My Budget',
                    'last_modified_on' => '2024-12-04T15:30:00Z',
                ],
            ],
        ]),
    ]);

    $service = new YnabService;
    $budget = $service->getBudgetDetails();

    expect($budget['name'])->toBe('My Budget');
    expect($budget['last_modified_on'])->toBe('2024-12-04T15:30:00Z');
});

test('getBudgetDetails returns null when not configured', function () {
    config([
        'services.ynab.token' => '',
        'services.ynab.budget_id' => '',
    ]);

    $service = new YnabService;

    expect($service->getBudgetDetails())->toBeNull();
});

test('getAgeOfMoney returns the age of money value', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
        'services.ynab.base_url' => 'https://api.ynab.com/v1',
    ]);

    $currentMonth = now()->format('Y-m-01');

    Http::fake([
        "api.ynab.com/v1/budgets/test-budget-id/months/{$currentMonth}" => Http::response([
            'data' => [
                'month' => [
                    'age_of_money' => 42,
                ],
            ],
        ]),
    ]);

    $service = new YnabService;
    $ageOfMoney = $service->getAgeOfMoney();

    expect($ageOfMoney)->toBe(42);
});

test('getAgeOfMoney returns null when not configured', function () {
    config([
        'services.ynab.token' => '',
        'services.ynab.budget_id' => '',
    ]);

    $service = new YnabService;

    expect($service->getAgeOfMoney())->toBeNull();
});

test('getMonthlyData returns formatted monthly data', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
        'services.ynab.base_url' => 'https://api.ynab.com/v1',
    ]);

    $currentMonth = now()->format('Y-m-01');
    $lastMonth = now()->subMonth()->format('Y-m-01');
    $futureMonth = now()->addMonth()->format('Y-m-01');

    Http::fake([
        'api.ynab.com/v1/budgets/test-budget-id/months' => Http::response([
            'data' => [
                'months' => [
                    [
                        'month' => $futureMonth, // Future month - should be filtered out
                        'income' => 50000000,
                        'activity' => -30000000,
                        'budgeted' => 45000000,
                        'to_be_budgeted' => 5000000,
                        'age_of_money' => 45,
                    ],
                    [
                        'month' => $currentMonth,
                        'income' => 50000000, // 50000 in milliunits
                        'activity' => -30000000, // -30000 in milliunits
                        'budgeted' => 45000000,
                        'to_be_budgeted' => 5000000,
                        'age_of_money' => 42,
                    ],
                    [
                        'month' => $lastMonth,
                        'income' => 48000000,
                        'activity' => -28000000,
                        'budgeted' => 43000000,
                        'to_be_budgeted' => 3000000,
                        'age_of_money' => 40,
                    ],
                ],
            ],
        ]),
    ]);

    $service = new YnabService;
    $monthlyData = $service->getMonthlyData(12);

    // Should not include future month
    expect($monthlyData)->toHaveCount(2);

    // Should be sorted by month descending (current first)
    expect($monthlyData[0]['month'])->toBe($currentMonth);
    expect($monthlyData[0]['income'])->toEqual(50000.0);
    expect($monthlyData[0]['activity'])->toEqual(-30000.0);
    expect($monthlyData[0]['age_of_money'])->toBe(42);

    expect($monthlyData[1]['month'])->toBe($lastMonth);
});

test('getMonthlyData returns empty array when not configured', function () {
    config([
        'services.ynab.token' => '',
        'services.ynab.budget_id' => '',
    ]);

    $service = new YnabService;

    expect($service->getMonthlyData())->toBe([]);
});

test('clearCache clears all YNAB cache keys', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
        'services.ynab.base_url' => 'https://api.ynab.com/v1',
    ]);

    // Set up some cached data
    Cache::put('ynab.accounts', ['test'], 300);
    Cache::put('ynab.budget', ['test'], 300);
    Cache::put('ynab.age_of_money', 42, 300);
    Cache::put('ynab.months.6', ['test'], 300);
    Cache::put('ynab.months.12', ['test'], 300);
    Cache::put('ynab.months.24', ['test'], 300);

    expect(Cache::has('ynab.accounts'))->toBeTrue();
    expect(Cache::has('ynab.budget'))->toBeTrue();
    expect(Cache::has('ynab.age_of_money'))->toBeTrue();
    expect(Cache::has('ynab.months.12'))->toBeTrue();

    $service = new YnabService;
    $service->clearCache();

    expect(Cache::has('ynab.accounts'))->toBeFalse();
    expect(Cache::has('ynab.budget'))->toBeFalse();
    expect(Cache::has('ynab.age_of_money'))->toBeFalse();
    expect(Cache::has('ynab.months.6'))->toBeFalse();
    expect(Cache::has('ynab.months.12'))->toBeFalse();
    expect(Cache::has('ynab.months.24'))->toBeFalse();
});

test('API errors return null or empty array gracefully', function () {
    config([
        'services.ynab.token' => 'test-token',
        'services.ynab.budget_id' => 'test-budget-id',
        'services.ynab.base_url' => 'https://api.ynab.com/v1',
    ]);

    Http::fake([
        '*' => Http::response(null, 500),
    ]);

    $service = new YnabService;

    expect($service->getAccounts())->toBe([]);
    expect($service->getBudgetDetails())->toBeNull();
    expect($service->getAgeOfMoney())->toBeNull();
    expect($service->getMonthlyData())->toBe([]);
});
