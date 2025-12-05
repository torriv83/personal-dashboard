<?php

namespace App\Livewire\Economy;

use App\Models\IncomeSetting;
use App\Services\YnabService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * @property-read array $accounts
 * @property-read int|null $ageOfMoney
 * @property-read array $monthlyData
 * @property-read float $totalBalance
 * @property-read string|null $lastModifiedAt
 * @property-read string|null $lastSyncedAt
 * @property-read bool $isYnabConfigured
 */
#[Layout('components.layouts.app')]
class Index extends Component
{
    public IncomeSetting $incomeSetting;

    public bool $isLoadingYnab = true;

    #[Validate('required|numeric|min:0')]
    public string $monthly_gross = '';

    #[Validate('required|numeric|min:0')]
    public string $monthly_net = '';

    #[Validate('nullable|string|max:50')]
    public string $tax_table = '';

    #[Validate('required|numeric|min:0')]
    public string $base_support = '';

    public function mount(): void
    {
        $this->incomeSetting = IncomeSetting::instance();
        $this->fillForm();
    }

    public function loadYnabData(): void
    {
        // Trigger computed properties to load data
        /** @phpstan-ignore expr.resultUnused */
        [$this->accounts, $this->ageOfMoney, $this->monthlyData, $this->lastModifiedAt];

        $this->isLoadingYnab = false;
    }

    public function fillForm(): void
    {
        $this->monthly_gross = number_format($this->incomeSetting->monthly_gross, 0, '', '');
        $this->monthly_net = number_format($this->incomeSetting->monthly_net, 0, '', '');
        $this->tax_table = $this->incomeSetting->tax_table ?? '';
        $this->base_support = number_format($this->incomeSetting->base_support, 0, '', '');
    }

    public function saveIncomeSettings(): void
    {
        $this->validate();

        $this->incomeSetting->update([
            'monthly_gross' => $this->monthly_gross,
            'monthly_net' => $this->monthly_net,
            'tax_table' => $this->tax_table ?: null,
            'base_support' => $this->base_support,
        ]);

        $this->dispatch('close-modal', name: 'income-settings');
        $this->dispatch('toast', type: 'success', message: 'Inntektsinnstillingene ble lagret');
    }

    public function syncYnab(): void
    {
        $this->isLoadingYnab = true;

        $ynab = app(YnabService::class);
        $ynab->clearCache();

        // Re-fetch data by clearing computed cache (timestamp is set in YnabService when data is fetched)
        unset($this->accounts, $this->ageOfMoney, $this->monthlyData, $this->lastModifiedAt, $this->lastSyncedAt);

        // Reload data
        $this->loadYnabData();

        $this->dispatch('toast', type: 'success', message: 'YNAB-data oppdatert');
        $this->dispatch('syncCompleted');
    }

    #[Computed]
    public function accounts(): array
    {
        return app(YnabService::class)->getAccounts();
    }

    #[Computed]
    public function ageOfMoney(): ?int
    {
        return app(YnabService::class)->getAgeOfMoney();
    }

    #[Computed]
    public function monthlyData(): array
    {
        return app(YnabService::class)->getMonthlyData(12);
    }

    #[Computed]
    public function totalBalance(): float
    {
        return collect($this->accounts)->sum('balance');
    }

    #[Computed]
    public function lastModifiedAt(): ?string
    {
        $budget = app(YnabService::class)->getBudgetDetails();

        return $budget['last_modified_on'] ?? null;
    }

    #[Computed]
    public function lastSyncedAt(): ?string
    {
        return Cache::get('ynab.last_synced')?->format('d.m.Y \\k\\l. H:i');
    }

    #[Computed]
    public function isYnabConfigured(): bool
    {
        return app(YnabService::class)->isConfigured();
    }

    public function render()
    {
        return view('livewire.economy.index');
    }
}
