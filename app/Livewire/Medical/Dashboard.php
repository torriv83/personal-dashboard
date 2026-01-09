<?php

declare(strict_types=1);

namespace App\Livewire\Medical;

use App\Models\Category;
use App\Models\Equipment;
use App\Models\MedicalExpense;
use App\Models\Prescription;
use App\Models\Setting;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * @property-read array $stats
 * @property-read \Illuminate\Support\Collection $expiringPrescriptions
 * @property-read ?Prescription $nextExpiry
 * @property-read float $frikortLimit
 * @property-read float $frikortTotal
 * @property-read float $frikortProgress
 * @property-read float $frikortRemaining
 * @property-read bool $frikortAchieved
 * @property-read \Illuminate\Support\Collection $expenses
 */
#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    public bool $showAddExpenseModal = false;

    public bool $showExpenseHistoryModal = false;

    public bool $showFrikortSettingsModal = false;

    public ?int $editingExpenseId = null;

    public float|string $expenseAmount = '';

    public string $expenseDate = '';

    public string $expenseNote = '';

    public float|string $frikortLimitInput = '';

    #[Computed]
    public function stats(): array
    {
        return [
            [
                'label' => 'Antall utstyr',
                'value' => (string) Equipment::count(),
                'icon' => 'box',
            ],
            [
                'label' => 'Kategorier',
                'value' => (string) Category::count(),
                'icon' => 'folder',
            ],
            [
                'label' => 'Resepter',
                'value' => (string) Prescription::count(),
                'icon' => 'document',
            ],
        ];
    }

    #[Computed]
    public function expiringPrescriptions(): \Illuminate\Support\Collection
    {
        return Prescription::where('valid_to', '<=', now()->addDays(30))
            ->orderBy('valid_to')
            ->get()
            ->map(function ($prescription) {
                $daysLeft = (int) Carbon::now()->startOfDay()->diffInDays($prescription->valid_to, false);

                $prescription->daysLeft = $daysLeft;
                $prescription->status = match (true) {
                    $daysLeft <= 0 => 'expired',
                    $daysLeft <= 7 => 'danger',
                    default => 'warning',
                };

                return $prescription;
            });
    }

    #[Computed]
    public function nextExpiry(): ?Prescription
    {
        return $this->expiringPrescriptions->first();
    }

    #[Computed]
    public function frikortLimit(): float
    {
        return Setting::getFrikortLimit();
    }

    #[Computed]
    public function frikortTotal(): float
    {
        return (float) MedicalExpense::currentYear()->sum('amount');
    }

    #[Computed]
    public function frikortProgress(): float
    {
        if ($this->frikortLimit <= 0) {
            return 0;
        }

        return min(($this->frikortTotal / $this->frikortLimit) * 100, 100);
    }

    #[Computed]
    public function frikortRemaining(): float
    {
        return max($this->frikortLimit - $this->frikortTotal, 0);
    }

    #[Computed]
    public function frikortAchieved(): bool
    {
        return $this->frikortTotal >= $this->frikortLimit;
    }

    #[Computed]
    public function expenses(): \Illuminate\Support\Collection
    {
        return MedicalExpense::currentYear()
            ->orderBy('expense_date', 'desc')
            ->get();
    }

    public function openAddExpenseModal(): void
    {
        $this->reset(['editingExpenseId', 'expenseAmount', 'expenseNote']);
        $this->expenseDate = now()->format('Y-m-d');
        $this->showAddExpenseModal = true;
    }

    public function editExpense(int $expenseId): void
    {
        $expense = MedicalExpense::findOrFail($expenseId);
        $this->editingExpenseId = $expense->id;
        $this->expenseAmount = $expense->amount;
        $this->expenseDate = $expense->expense_date->format('Y-m-d');
        $this->expenseNote = $expense->note ?? '';
        $this->showExpenseHistoryModal = false;
        $this->showAddExpenseModal = true;
    }

    public function saveExpense(): void
    {
        $this->validate([
            'expenseAmount' => 'required|numeric|min:0|max:99999.99',
            'expenseDate' => 'required|date',
            'expenseNote' => 'nullable|string|max:500',
        ], [
            'expenseAmount.required' => 'Beløp er påkrevd',
            'expenseAmount.numeric' => 'Beløp må være et tall',
            'expenseAmount.min' => 'Beløp må være minst 0',
            'expenseAmount.max' => 'Beløp kan ikke være over 99 999,99',
            'expenseDate.required' => 'Dato er påkrevd',
            'expenseDate.date' => 'Ugyldig dato',
            'expenseNote.max' => 'Notis kan ikke være lengre enn 500 tegn',
        ]);

        $date = Carbon::parse($this->expenseDate);

        if ($this->editingExpenseId) {
            $expense = MedicalExpense::findOrFail($this->editingExpenseId);
            $expense->update([
                'amount' => $this->expenseAmount,
                'expense_date' => $date,
                'note' => $this->expenseNote ?: null,
                'year' => $date->year,
            ]);
        } else {
            MedicalExpense::create([
                'amount' => $this->expenseAmount,
                'expense_date' => $date,
                'note' => $this->expenseNote ?: null,
                'year' => $date->year,
            ]);
        }

        $this->closeAddExpenseModal();
    }

    public function deleteExpense(int $expenseId): void
    {
        MedicalExpense::findOrFail($expenseId)->delete();
    }

    public function closeAddExpenseModal(): void
    {
        $this->showAddExpenseModal = false;
        $this->reset(['editingExpenseId', 'expenseAmount', 'expenseDate', 'expenseNote']);
    }

    public function openExpenseHistoryModal(): void
    {
        $this->showExpenseHistoryModal = true;
    }

    public function closeExpenseHistoryModal(): void
    {
        $this->showExpenseHistoryModal = false;
    }

    public function openFrikortSettingsModal(): void
    {
        $this->frikortLimitInput = $this->frikortLimit;
        $this->showFrikortSettingsModal = true;
    }

    public function closeFrikortSettingsModal(): void
    {
        $this->showFrikortSettingsModal = false;
        $this->reset('frikortLimitInput');
    }

    public function saveFrikortLimit(): void
    {
        $this->validate([
            'frikortLimitInput' => 'required|numeric|min:0|max:99999',
        ], [
            'frikortLimitInput.required' => 'Frikortgrense er påkrevd',
            'frikortLimitInput.numeric' => 'Frikortgrense må være et tall',
            'frikortLimitInput.min' => 'Frikortgrense må være minst 0',
            'frikortLimitInput.max' => 'Frikortgrense kan ikke være over 99 999',
        ]);

        Setting::setFrikortLimit((float) $this->frikortLimitInput);
        $this->closeFrikortSettingsModal();
    }

    public function render()
    {
        return view('livewire.medical.dashboard');
    }
}
