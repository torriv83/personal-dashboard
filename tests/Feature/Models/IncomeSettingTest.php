<?php

declare(strict_types=1);

use App\Models\IncomeSetting;

beforeEach(function () {
    // Clean up any existing income settings
    IncomeSetting::query()->delete();
});

test('instance creates a new record if none exists', function () {
    expect(IncomeSetting::count())->toBe(0);

    $instance = IncomeSetting::instance();

    expect($instance)->toBeInstanceOf(IncomeSetting::class);
    expect(IncomeSetting::count())->toBe(1);
});

test('instance returns existing record if one exists', function () {
    $existing = IncomeSetting::create([
        'monthly_gross' => 50000,
        'monthly_net' => 35000,
        'tax_table' => '7100',
        'base_support' => 3000,
    ]);

    $instance = IncomeSetting::instance();

    expect($instance->id)->toBe($existing->id);
    expect(IncomeSetting::count())->toBe(1);
});

test('instance creates record with default values', function () {
    $instance = IncomeSetting::instance();

    expect((float) $instance->monthly_gross)->toBe(0.0);
    expect((float) $instance->monthly_net)->toBe(0.0);
    expect($instance->tax_table)->toBeNull();
    expect((float) $instance->base_support)->toBe(0.0);
});

test('yearly_gross includes base_support', function () {
    $setting = IncomeSetting::create([
        'monthly_gross' => 50000,
        'monthly_net' => 35000,
        'base_support' => 3000,
    ]);

    // (50000 + 3000) * 12 = 636000
    expect($setting->yearly_gross)->toBe(636000.0);
});

test('yearly_net includes base_support', function () {
    $setting = IncomeSetting::create([
        'monthly_gross' => 50000,
        'monthly_net' => 35000,
        'base_support' => 3000,
    ]);

    // (35000 + 3000) * 12 = 456000
    expect($setting->yearly_net)->toBe(456000.0);
});

test('tax_percentage calculates correctly', function () {
    $setting = IncomeSetting::create([
        'monthly_gross' => 50000,
        'monthly_net' => 35000,
        'base_support' => 0,
    ]);

    // (1 - (35000 / 50000)) * 100 = 30%
    expect($setting->tax_percentage)->toBe(30.0);
});

test('tax_percentage returns zero when monthly_gross is zero', function () {
    $setting = IncomeSetting::create([
        'monthly_gross' => 0,
        'monthly_net' => 0,
        'base_support' => 0,
    ]);

    expect($setting->tax_percentage)->toBe(0.0);
});

test('tax_percentage handles decimal values', function () {
    $setting = IncomeSetting::create([
        'monthly_gross' => 45000,
        'monthly_net' => 33750,
        'base_support' => 0,
    ]);

    // (1 - (33750 / 45000)) * 100 = 25%
    expect($setting->tax_percentage)->toBe(25.0);
});

test('casts decimal values correctly', function () {
    $setting = IncomeSetting::create([
        'monthly_gross' => 50000.50,
        'monthly_net' => 35000.75,
        'base_support' => 3000.25,
    ]);

    $setting->refresh();

    expect($setting->monthly_gross)->toBe('50000.50');
    expect($setting->monthly_net)->toBe('35000.75');
    expect($setting->base_support)->toBe('3000.25');
});
