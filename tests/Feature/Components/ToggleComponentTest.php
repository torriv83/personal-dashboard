<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Blade;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

it('renders toggle component with default size', function () {
    $html = Blade::render('<x-toggle label="Test Toggle" />');

    expect($html)
        ->toContain('w-12 h-7') // Default container size
        ->toContain('w-5 h-5') // Default circle size
        ->toContain('translate-x-5') // Default translation
        ->toContain('role="switch"')
        ->toContain('Test Toggle');
});

it('renders toggle component with sm size', function () {
    $html = Blade::render('<x-toggle label="Small Toggle" size="sm" />');

    expect($html)
        ->toContain('w-10 h-6') // Small container size
        ->toContain('w-4 h-4') // Small circle size
        ->toContain('translate-x-4') // Small translation
        ->toContain('Small Toggle');
});

it('renders toggle with label and description', function () {
    $html = Blade::render('<x-toggle label="My Label" description="My Description" />');

    expect($html)
        ->toContain('My Label')
        ->toContain('My Description')
        ->toContain('text-sm font-medium')
        ->toContain('text-xs text-muted');
});

it('renders toggle with disabled state', function () {
    $html = Blade::render('<x-toggle label="Disabled Toggle" :disabled="true" />');

    expect($html)
        ->toContain('disabled')
        ->toContain('disabled:opacity-50')
        ->toContain('disabled:cursor-not-allowed');
});

it('renders toggle with checked state', function () {
    $html = Blade::render('<x-toggle label="Checked Toggle" :checked="true" />');

    expect($html)
        ->toContain('checked')
        ->toContain('bg-accent');
});

it('renders toggle with accessibility attributes', function () {
    $html = Blade::render('<x-toggle label="Accessible Toggle" />');

    expect($html)
        ->toContain('role="switch"')
        ->toContain('aria-checked');
});

it('supports wire:model binding in Livewire context', function () {
    // This test verifies that wire:model is supported by checking the component code
    // Actual wire:model testing is done in the Livewire component tests
    $togglePath = base_path('resources/views/components/toggle.blade.php');
    $content = file_get_contents($togglePath);

    expect($content)
        ->toContain('@entangle')
        ->toContain('$wireModel');
});

it('renders toggle without label when not provided', function () {
    $html = Blade::render('<x-toggle />');

    expect($html)
        ->toContain('role="switch"')
        ->toContain('bg-border')
        ->not->toContain('text-sm font-medium');
});

it('generates unique id when not provided', function () {
    $html1 = Blade::render('<x-toggle />');
    $html2 = Blade::render('<x-toggle />');

    expect($html1)->toContain('toggle-')
        ->and($html2)->toContain('toggle-')
        ->and($html1)->not->toBe($html2);
});

it('uses custom id when provided', function () {
    $html = Blade::render('<x-toggle id="custom-toggle-id" />');

    expect($html)->toContain('custom-toggle-id');
});

it('renders with focus styles', function () {
    $html = Blade::render('<x-toggle />');

    expect($html)
        ->toContain('focus:outline-none')
        ->toContain('focus:ring-2')
        ->toContain('focus:ring-accent')
        ->toContain('focus:ring-offset-2');
});

it('renders with transition classes', function () {
    $html = Blade::render('<x-toggle />');

    expect($html)
        ->toContain('transition-colors')
        ->toContain('transition-transform');
});
