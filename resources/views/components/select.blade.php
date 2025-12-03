@props([
    'label' => null,
    'error' => null,
    'id' => null,
    'placeholder' => 'Velg...',
])

@php
$inputId = $id ?? 'select-' . uniqid();
$hasError = $error !== null;
$selectClasses = 'w-full bg-input border rounded-md px-3 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background transition-colors appearance-none cursor-pointer ' .
    ($hasError ? 'border-destructive focus:ring-destructive' : 'border-border focus:ring-accent');
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-foreground mb-1.5">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $inputId }}"
            {{ $attributes->merge(['class' => $selectClasses]) }}
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>

        {{-- Dropdown arrow --}}
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>

    @if($error)
        <p class="mt-1.5 text-sm text-destructive">
            {{ $error }}
        </p>
    @endif
</div>
