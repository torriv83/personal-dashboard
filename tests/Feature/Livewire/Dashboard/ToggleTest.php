<?php

declare(strict_types=1);

use App\Livewire\Dashboard\Index;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('toggles widget visibility from visible to hidden', function () {
    $component = Livewire::test(Index::class);

    $widgets = $component->get('widgets');
    $firstWidget = $widgets[0];

    expect($firstWidget['visible'])->toBeTrue();

    $component->call('toggleVisibility', $firstWidget['id'])
        ->assertSet('widgets.0.visible', false);

    // Verify setting was saved
    $saved = json_decode(Setting::get('dashboard_widgets'), true);
    $savedWidget = collect($saved)->firstWhere('id', $firstWidget['id']);

    expect($savedWidget['visible'])->toBeFalse();
});

it('toggles widget visibility from hidden to visible', function () {
    // First hide a widget
    $component = Livewire::test(Index::class);
    $widgets = $component->get('widgets');
    $firstWidget = $widgets[0];

    $component->call('toggleVisibility', $firstWidget['id']);

    // Now toggle it back
    $component->call('toggleVisibility', $firstWidget['id'])
        ->assertSet('widgets.0.visible', true);

    // Verify setting was saved
    $saved = json_decode(Setting::get('dashboard_widgets'), true);
    $savedWidget = collect($saved)->firstWhere('id', $firstWidget['id']);

    expect($savedWidget['visible'])->toBeTrue();
});

it('toggles specific widget by id', function () {
    $component = Livewire::test(Index::class);
    $widgets = $component->get('widgets');

    // Find the 'wishlist' widget
    $wishlistIndex = null;
    foreach ($widgets as $index => $widget) {
        if ($widget['id'] === 'wishlist') {
            $wishlistIndex = $index;
            break;
        }
    }

    expect($wishlistIndex)->not->toBeNull();
    expect($widgets[$wishlistIndex]['visible'])->toBeTrue();

    $component->call('toggleVisibility', 'wishlist')
        ->assertSet("widgets.{$wishlistIndex}.visible", false);
});

it('only toggles the specified widget', function () {
    $component = Livewire::test(Index::class);
    $widgets = $component->get('widgets');

    // Toggle first widget
    $component->call('toggleVisibility', $widgets[0]['id']);

    // Check other widgets remain unchanged
    $updatedWidgets = $component->get('widgets');
    for ($i = 1; $i < count($widgets); $i++) {
        expect($updatedWidgets[$i]['visible'])->toBe($widgets[$i]['visible']);
    }
});

it('persists widget visibility across component mounts', function () {
    // First component instance - toggle a widget
    $component1 = Livewire::test(Index::class);
    $widgets = $component1->get('widgets');
    $firstWidget = $widgets[0];

    $component1->call('toggleVisibility', $firstWidget['id']);

    // Second component instance - should load saved state
    $component2 = Livewire::test(Index::class);
    $loadedWidgets = $component2->get('widgets');

    expect($loadedWidgets[0]['visible'])->toBeFalse();
});

it('shows all widgets in settings panel', function () {
    $component = Livewire::test(Index::class);
    $widgets = $component->get('widgets');

    foreach ($widgets as $widget) {
        $component->assertSee($widget['name']);
    }
});

it('handles toggling non-existent widget gracefully', function () {
    $component = Livewire::test(Index::class);
    $widgetsBefore = $component->get('widgets');

    $component->call('toggleVisibility', 'non-existent-widget-id');

    $widgetsAfter = $component->get('widgets');

    // Widgets should remain unchanged
    expect($widgetsAfter)->toBe($widgetsBefore);
});

it('maintains widget order when toggling visibility', function () {
    $component = Livewire::test(Index::class);
    $initialWidgets = $component->get('widgets');
    $initialIds = array_column($initialWidgets, 'id');

    // Toggle visibility of multiple widgets
    $component->call('toggleVisibility', $initialWidgets[0]['id']);
    $component->call('toggleVisibility', $initialWidgets[2]['id']);

    $finalWidgets = $component->get('widgets');
    $finalIds = array_column($finalWidgets, 'id');

    // Order should remain the same
    expect($finalIds)->toBe($initialIds);
});
