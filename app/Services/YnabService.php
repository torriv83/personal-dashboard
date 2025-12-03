<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class YnabService
{
    private string $baseUrl;

    private string $token;

    private string $budgetId;

    public function __construct()
    {
        $this->baseUrl = config('services.ynab.base_url');
        $this->token = config('services.ynab.token');
        $this->budgetId = config('services.ynab.budget_id');
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

        return Cache::rememberForever('ynab.accounts', function () {
            $response = $this->request("/budgets/{$this->budgetId}/accounts");

            if (! $response) {
                return [];
            }

            return collect($response['data']['accounts'] ?? [])
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
        });
    }

    /**
     * Get budget details including age of money.
     */
    public function getBudgetDetails(): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return Cache::rememberForever('ynab.budget', function () {
            $response = $this->request("/budgets/{$this->budgetId}");

            if (! $response) {
                return null;
            }

            $budget = $response['data']['budget'] ?? null;

            return $budget ? [
                'name' => $budget['name'],
                'last_modified_on' => $budget['last_modified_on'],
            ] : null;
        });
    }

    /**
     * Get age of money from current month.
     */
    public function getAgeOfMoney(): ?int
    {
        if (! $this->isConfigured()) {
            return null;
        }

        return Cache::rememberForever('ynab.age_of_money', function () {
            $currentMonth = now()->format('Y-m-01');
            $response = $this->request("/budgets/{$this->budgetId}/months/{$currentMonth}");

            if (! $response) {
                return null;
            }

            return $response['data']['month']['age_of_money'] ?? null;
        });
    }

    /**
     * Get monthly summaries for the last N months.
     */
    public function getMonthlyData(int $months = 12): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        return Cache::rememberForever("ynab.months.{$months}", function () use ($months) {
            $response = $this->request("/budgets/{$this->budgetId}/months");

            if (! $response) {
                return [];
            }

            $currentMonth = now()->format('Y-m-01');

            return collect($response['data']['months'] ?? [])
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
        });
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
     * Make a request to the YNAB API.
     */
    private function request(string $endpoint): ?array
    {
        try {
            $response = Http::withToken($this->token)
                ->accept('application/json')
                ->get($this->baseUrl.$endpoint);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            report($e);

            return null;
        }
    }
}
