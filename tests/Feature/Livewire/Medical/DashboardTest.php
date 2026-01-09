<?php

declare(strict_types=1);

use App\Livewire\Medical\Dashboard;
use App\Models\Category;
use App\Models\Equipment;
use App\Models\MedicalExpense;
use App\Models\Prescription;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('renders the medical dashboard', function () {
    $this->get(route('medical.dashboard'))
        ->assertOk()
        ->assertSee('Medisinsk');
});

it('shows correct stats', function () {
    Category::factory()->count(3)->create();
    Equipment::factory()->count(5)->create();
    Prescription::factory()->count(7)->create();

    Livewire::test(Dashboard::class)
        ->assertSee('5')
        ->assertSee('7');
});

it('shows expiring prescriptions within 30 days', function () {
    Prescription::factory()->create([
        'name' => 'Expiring Soon Medicine',
        'valid_to' => now()->addDays(5),
    ]);
    Prescription::factory()->create([
        'name' => 'Far Future Medicine',
        'valid_to' => now()->addDays(60),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Expiring Soon Medicine')
        ->assertDontSee('Far Future Medicine');
});

it('shows next expiry alert for most critical prescription', function () {
    Prescription::factory()->create([
        'name' => 'Later Expiry',
        'valid_to' => now()->addDays(20),
    ]);
    Prescription::factory()->create([
        'name' => 'Critical Expiry',
        'valid_to' => now()->addDays(3),
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Critical Expiry')
        ->assertSee('3');
});

it('shows expired prescriptions', function () {
    Prescription::factory()->expired()->create([
        'name' => 'Expired Medicine',
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Expired Medicine')
        ->assertSee('Utl');
});

it('shows quick links to equipment and prescriptions', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('Utstyr')
        ->assertSee('Resepter');
});

// Frikort tests
it('displays frikort card with progress', function () {
    Setting::setFrikortLimit(3000);
    MedicalExpense::factory()->create([
        'amount' => 1500,
        'year' => now()->year,
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Frikort ' . now()->year)
        ->assertSee('1 500 kr')
        ->assertSee('3 000 kr')
        ->assertSee('1 500 kr til frikort');
});

it('shows frikort achieved when limit is reached', function () {
    Setting::setFrikortLimit(2000);
    MedicalExpense::factory()->create([
        'amount' => 2500,
        'year' => now()->year,
    ]);

    Livewire::test(Dashboard::class)
        ->assertSee('Frikort oppnådd!');
});

it('can open add expense modal', function () {
    Livewire::test(Dashboard::class)
        ->call('openAddExpenseModal')
        ->assertSet('showAddExpenseModal', true)
        ->assertSet('expenseDate', now()->format('Y-m-d'));
});

it('can save new expense', function () {
    Livewire::test(Dashboard::class)
        ->set('expenseAmount', 450.50)
        ->set('expenseDate', now()->format('Y-m-d'))
        ->set('expenseNote', 'Apotek 1')
        ->call('saveExpense')
        ->assertSet('showAddExpenseModal', false);

    expect(MedicalExpense::count())->toBe(1);
    expect(MedicalExpense::first()->amount)->toBe('450.50');
    expect(MedicalExpense::first()->note)->toBe('Apotek 1');
});

it('validates expense amount is required', function () {
    Livewire::test(Dashboard::class)
        ->set('expenseAmount', '')
        ->set('expenseDate', now()->format('Y-m-d'))
        ->call('saveExpense')
        ->assertHasErrors(['expenseAmount' => 'required']);
});

it('validates expense date is required', function () {
    Livewire::test(Dashboard::class)
        ->set('expenseAmount', 100)
        ->set('expenseDate', '')
        ->call('saveExpense')
        ->assertHasErrors(['expenseDate' => 'required']);
});

it('can edit existing expense', function () {
    $expense = MedicalExpense::factory()->create([
        'amount' => 300,
        'expense_date' => now()->subDays(5),
        'note' => 'Original note',
        'year' => now()->year,
    ]);

    Livewire::test(Dashboard::class)
        ->call('editExpense', $expense->id)
        ->assertSet('editingExpenseId', $expense->id)
        ->assertSet('expenseAmount', '300.00')
        ->assertSet('expenseNote', 'Original note')
        ->set('expenseAmount', 350)
        ->set('expenseNote', 'Updated note')
        ->call('saveExpense');

    $expense->refresh();
    expect($expense->amount)->toBe('350.00');
    expect($expense->note)->toBe('Updated note');
});

it('can delete expense', function () {
    $expense = MedicalExpense::factory()->create(['year' => now()->year]);

    Livewire::test(Dashboard::class)
        ->call('deleteExpense', $expense->id);

    expect(MedicalExpense::count())->toBe(0);
});

it('can open expense history modal', function () {
    Livewire::test(Dashboard::class)
        ->call('openExpenseHistoryModal')
        ->assertSet('showExpenseHistoryModal', true);
});

it('shows expenses in history modal', function () {
    MedicalExpense::factory()->create([
        'amount' => 500,
        'note' => 'Test expense',
        'year' => now()->year,
    ]);

    Livewire::test(Dashboard::class)
        ->call('openExpenseHistoryModal')
        ->assertSee('Test expense')
        ->assertSee('500 kr');
});

it('can open frikort settings modal', function () {
    Setting::setFrikortLimit(3000);

    Livewire::test(Dashboard::class)
        ->call('openFrikortSettingsModal')
        ->assertSet('showFrikortSettingsModal', true)
        ->assertSet('frikortLimitInput', 3000);
});

it('can update frikort limit', function () {
    Setting::setFrikortLimit(3000);

    Livewire::test(Dashboard::class)
        ->call('openFrikortSettingsModal')
        ->set('frikortLimitInput', 3500)
        ->call('saveFrikortLimit')
        ->assertSet('showFrikortSettingsModal', false);

    expect(Setting::getFrikortLimit())->toBe(3500.0);
});

it('only shows expenses from current year', function () {
    MedicalExpense::factory()->create([
        'amount' => 100,
        'year' => now()->year,
        'expense_date' => now(),
    ]);
    MedicalExpense::factory()->create([
        'amount' => 200,
        'year' => now()->subYear()->year,
        'expense_date' => now()->subYear(),
    ]);

    $component = Livewire::test(Dashboard::class);

    expect($component->get('expenses')->count())->toBe(1);
    expect($component->get('frikortTotal'))->toBe(100.0);
});
