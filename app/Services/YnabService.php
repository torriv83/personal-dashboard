<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class YnabService
{
    private string $baseUrl;

    private ?string $token;

    private ?string $budgetId;

    /**
     * Errors that occurred during the current request cycle.
     *
     * @var array<string, string>
     */
    private array $errors = [];

    public function __construct()
    {
        $this->baseUrl = config('services.ynab.base_url', '');
        $this->token = config('services.ynab.token');
        $this->budgetId = config('services.ynab.budget_id');
    }

    /**
     * Get any errors that occurred during API calls.
     *
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if any errors occurred.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Clear stored errors.
     */
    public function clearErrors(): void
    {
        $this->errors = [];
    }

    /**
     * Check if YNAB is configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->token) && ! empty($this->budgetId);
    }

    /**
     * Get all accounts with balances.
     */
    public function getAccounts(): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        // Check cache first
        if (Cache::has('ynab.accounts')) {
            return Cache::get('ynab.accounts');
        }

        // Fetch from API
        $response = $this->request("/budgets/{$this->budgetId}/accounts", 'kontoer');

        if (! $response) {
            return [];
        }

        $accounts = collect($response['data']['accounts'] ?? [])
            ->filter(fn ($account) => ! $account['deleted'] && ! $account['closed'])
            ->map(fn ($account) => [
                'id' => $account['id'],
                'name' => $account['name'],
                'type' => $account['type'],
                'balance' => $account['balance'] / 1000, // YNAB uses milliunits
                'cleared_balance' => $account['cleared_balance'] / 1000,
                'last_reconciled_at' => $account['last_reconciled_at'],
            ])
            ->values()
            ->toArray();

        // Only cache if we got actual data
        if (! empty($accounts)) {
            Cache::forever('ynab.accounts', $accounts);
            $this->updateSyncTimestamp();
        }

        return $accounts;
    }

    /**
     * Get budget details including age of money.
     */
    public function getBudgetDetails(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // Check cache first
        if (Cache::has('ynab.budget')) {
            return Cache::get('ynab.budget');
        }

        // Fetch from API
        $response = $this->request("/budgets/{$this->budgetId}", 'budsjettdetaljer');

        if (! $response) {
            return null;
        }

        $budget = $response['data']['budget'] ?? null;

        $details = $budget ? [
            'name' => $budget['name'],
            'last_modified_on' => $budget['last_modified_on'],
        ] : null;

        // Only cache if we got actual data
        if ($details) {
            Cache::forever('ynab.budget', $details);
            $this->updateSyncTimestamp();
        }

        return $details;
    }

    /**
     * Get age of money from current month.
     */
    public function getAgeOfMoney(): ?int
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // Check cache first
        if (Cache::has('ynab.age_of_money')) {
            $cached = Cache::get('ynab.age_of_money');

            return $cached !== null ? (int) $cached : null;
        }

        // Fetch from API
        $currentMonth = now()->format('Y-m-01');
        $response = $this->request("/budgets/{$this->budgetId}/months/{$currentMonth}", 'age of money');

        if (! $response) {
            return null;
        }

        $ageOfMoney = $response['data']['month']['age_of_money'] ?? null;

        // Cast to int and cache if we got actual data
        if ($ageOfMoney !== null) {
            $ageOfMoney = (int) $ageOfMoney;
            Cache::forever('ynab.age_of_money', $ageOfMoney);
            $this->updateSyncTimestamp();
        }

        return $ageOfMoney;
    }

    /**
     * Get monthly summaries for the last N months.
     */
    public function getMonthlyData(int $months = 12): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $cacheKey = "ynab.months.{$months}";

        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Fetch from API
        $response = $this->request("/budgets/{$this->budgetId}/months", 'månedsdata');

        if (! $response) {
            return [];
        }

        $currentMonth = now()->format('Y-m-01');

        $monthlyData = collect($response['data']['months'] ?? [])
            ->filter(fn ($month) => $month['month'] <= $currentMonth)
            ->sortByDesc('month')
            ->take($months)
            ->map(fn ($month) => [
                'month' => $month['month'],
                'income' => $month['income'] / 1000,
                'activity' => $month['activity'] / 1000, // Negative = expenses
                'budgeted' => $month['budgeted'] / 1000,
                'to_be_budgeted' => $month['to_be_budgeted'] / 1000,
                'age_of_money' => $month['age_of_money'],
            ])
            ->values()
            ->toArray();

        // Only cache if we got actual data
        if (! empty($monthlyData)) {
            Cache::forever($cacheKey, $monthlyData);
            $this->updateSyncTimestamp();
        }

        return $monthlyData;
    }

    /**
     * Clear all YNAB cache.
     */
    public function clearCache(): void
    {
        Cache::forget('ynab.accounts');
        Cache::forget('ynab.budget');
        Cache::forget('ynab.age_of_money');

        // Clear monthly data cache for different month counts
        foreach ([6, 12, 24] as $months) {
            Cache::forget("ynab.months.{$months}");
        }
    }

    /**
     * Update the last synced timestamp.
     */
    private function updateSyncTimestamp(): void
    {
        Cache::forever('ynab.last_synced', now());
    }

    /**
     * Make a request to the YNAB API.
     */
    private function request(string $endpoint, string $dataName = 'data'): ?array
    {
        try {
            $response = Http::withToken($this->token)
                ->accept('application/json')
                ->timeout(30)
                ->get($this->baseUrl . $endpoint);

            if ($response->successful()) {
                return $response->json();
            }

            // Track the error with user-friendly message
            $status = $response->status();
            $this->errors[$dataName] = match (true) {
                $status === 401 => 'Ugyldig API-token',
                $status === 404 => 'Budsjett ikke funnet',
                $status === 429 => 'For mange forespørsler - vent litt',
                $status >= 500 => 'YNAB-serveren er utilgjengelig',
                default => "Feil ved henting ({$status})",
            };

            report(new \Exception("YNAB API error for {$dataName}: HTTP {$status} - {$endpoint}"));

            return null;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->errors[$dataName] = 'Kunne ikke koble til YNAB';
            report($e);

            return null;
        } catch (\Exception $e) {
            $this->errors[$dataName] = 'Uventet feil ved henting';
            report($e);

            return null;
        }
    }
}
