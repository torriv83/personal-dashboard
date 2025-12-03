<?php

namespace App\Livewire\Dashboard;

use App\Models\Prescription;
use App\Models\Shift;
use App\Models\WishlistItem;
use App\Services\YnabService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    /**
     * Get the next upcoming work shift.
     */
    #[Computed]
    public function nextShift(): ?Shift
    {
        return Shift::worked()
            ->upcoming()
            ->with('assistant')
            ->orderBy('starts_at')
            ->first();
    }

    /**
     * Get count of prescriptions expiring within 60 days.
     */
    #[Computed]
    public function expiringPrescriptionsCount(): int
    {
        return Prescription::query()
            ->where('valid_to', '<=', now()->addDays(60))
            ->where('valid_to', '>=', now())
            ->count();
    }

    /**
     * Get "To Be Budgeted" from YNAB for current month.
     */
    #[Computed]
    public function toBeBudgeted(): ?float
    {
        $ynab = app(YnabService::class);

        if (! $ynab->isConfigured()) {
            return null;
        }

        $monthlyData = $ynab->getMonthlyData(1);

        return $monthlyData[0]['to_be_budgeted'] ?? null;
    }

    /**
     * Get count of active wishlist items (not purchased/saved).
     */
    #[Computed]
    public function wishlistCount(): int
    {
        return WishlistItem::query()
            ->whereNotIn('status', ['saved', 'purchased'])
            ->count();
    }

    public function render()
    {
        return view('livewire.dashboard.index');
    }
}
