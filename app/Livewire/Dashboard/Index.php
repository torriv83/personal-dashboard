<?php

namespace App\Livewire\Dashboard;

use App\Models\Prescription;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\WishlistItem;
use App\Services\WeatherService;
use App\Services\YnabService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public array $widgets = [];

    public bool $showSettings = false;

    public bool $showForecastModal = false;

    /**
     * Default widget configuration.
     */
    protected array $defaultWidgets = [
        [
            'id' => 'bpa',
            'name' => 'BPA',
            'route' => 'bpa.dashboard',
            'icon' => 'clock',
            'visible' => true,
        ],
        [
            'id' => 'medical',
            'name' => 'Medisinsk',
            'route' => 'medical.dashboard',
            'icon' => 'heart',
            'visible' => true,
        ],
        [
            'id' => 'economy',
            'name' => 'Økonomi',
            'route' => 'economy',
            'icon' => 'currency',
            'visible' => true,
        ],
        [
            'id' => 'wishlist',
            'name' => 'Ønskeliste',
            'route' => 'wishlist',
            'icon' => 'star',
            'visible' => true,
        ],
    ];

    public function mount(): void
    {
        $this->loadWidgets();
    }

    /**
     * Load widget configuration from settings.
     */
    protected function loadWidgets(): void
    {
        $saved = Setting::get('dashboard_widgets');

        if ($saved) {
            $savedWidgets = json_decode($saved, true);
            $this->widgets = $this->mergeWithDefaults($savedWidgets);
        } else {
            $this->widgets = $this->defaultWidgets;
        }
    }

    /**
     * Merge saved widgets with defaults to handle new widgets.
     */
    protected function mergeWithDefaults(array $savedWidgets): array
    {
        $savedIds = array_column($savedWidgets, 'id');
        $result = $savedWidgets;

        // Add any new default widgets that aren't in saved config
        foreach ($this->defaultWidgets as $default) {
            if (! in_array($default['id'], $savedIds)) {
                $result[] = $default;
            }
        }

        // Update static properties from defaults
        foreach ($result as &$widget) {
            foreach ($this->defaultWidgets as $default) {
                if ($widget['id'] === $default['id']) {
                    $widget['name'] = $default['name'];
                    $widget['route'] = $default['route'];
                    $widget['icon'] = $default['icon'];
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Save widget configuration to settings.
     */
    protected function saveWidgets(): void
    {
        $toSave = array_map(fn ($w) => [
            'id' => $w['id'],
            'visible' => $w['visible'],
        ], $this->widgets);

        Setting::set('dashboard_widgets', json_encode($toSave));
    }

    /**
     * Update widget order (called by x-sort).
     */
    public function updateOrder(string $item, int $position): void
    {
        $widgetId = $item;

        $movingIndex = null;
        foreach ($this->widgets as $index => $widget) {
            if ($widget['id'] === $widgetId) {
                $movingIndex = $index;
                break;
            }
        }

        if ($movingIndex === null) {
            return;
        }

        $widget = $this->widgets[$movingIndex];
        array_splice($this->widgets, $movingIndex, 1);
        array_splice($this->widgets, $position, 0, [$widget]);
        $this->widgets = array_values($this->widgets);

        $this->saveWidgets();
    }

    /**
     * Toggle widget visibility.
     */
    public function toggleVisibility(string $widgetId): void
    {
        foreach ($this->widgets as &$widget) {
            if ($widget['id'] === $widgetId) {
                $widget['visible'] = ! $widget['visible'];
                break;
            }
        }

        $this->saveWidgets();
    }

    /**
     * Reset to default configuration.
     */
    public function resetToDefaults(): void
    {
        $this->widgets = $this->defaultWidgets;
        $this->saveWidgets();
        $this->showSettings = false;
    }

    /**
     * Get visible widgets.
     */
    #[Computed]
    public function visibleWidgets(): array
    {
        return array_filter($this->widgets, fn ($w) => $w['visible']);
    }

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

    /**
     * Get current weather data.
     *
     * @return array{
     *     temperature: float,
     *     symbol: string,
     *     description: string,
     *     wind_speed: float,
     *     precipitation: float,
     *     location: string,
     *     updated_at: string
     * }|null
     */
    #[Computed]
    public function weather(): ?array
    {
        return app(WeatherService::class)->getCurrentWeather();
    }

    /**
     * Get weekly forecast data.
     *
     * @return array<int, array{
     *     date: string,
     *     day_name: string,
     *     day_short: string,
     *     symbol: string,
     *     description: string,
     *     temp_high: float,
     *     temp_low: float,
     *     precipitation: float,
     *     wind_speed: float
     * }>
     */
    #[Computed]
    public function forecast(): array
    {
        return app(WeatherService::class)->getWeeklyForecast();
    }

    /**
     * Refresh weather data.
     */
    public function refreshWeather(): void
    {
        app(WeatherService::class)->clearCache();
        unset($this->weather);
        unset($this->forecast);
    }

    public function render()
    {
        return view('livewire.dashboard.index');
    }
}
