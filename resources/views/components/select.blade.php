@props([
    'label' => null,
    'error' => null,
    'id' => null,
    'placeholder' => 'Velg...',
    'inline' => false,
    'size' => 'md',
    'required' => false,
    'wrapperClass' => '',
])

@php
$wireModel = $attributes->wire('model')->value();
$inputId = $id ?? $wireModel ?? 'select-' . uniqid();
$errorKey = $wireModel ?? $inputId;
$hasError = $error !== null || $errors->has($errorKey);
$errorMessage = $error ?? $errors->first($errorKey);

$sizeClasses = match($size) {
    'sm' => 'px-2 py-1 text-xs',
    'md' => 'px-3 py-2 text-sm',
    'lg' => 'px-4 py-2.5 text-base',
    default => 'px-3 py-2 text-sm',
};

$widthClass = $inline ? '' : 'w-full';
$selectClasses = $widthClass . ' bg-input border rounded-lg text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-colors appearance-none cursor-pointer ' .
    $sizeClasses . ' ' .
    ($hasError ? 'border-destructive focus:ring-destructive' : 'border-border');
@endphp

@if($inline)
    <div class="relative {{ $wrapperClass }}">
        <select
            id="{{ $inputId }}"
            {{ $attributes->merge(['class' => $selectClasses . ' w-full']) }}
        >
            @if($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            {{ $slot }}
        </select>
        <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
            <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </div>
    </div>
@else
    <div class="w-full">
        @if($label)
            <label for="{{ $inputId }}" class="block text-sm font-medium text-muted mb-1">
                {{ $label }}@if($required) <span class="text-destructive">*</span>@endif
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
            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
        </div>

        @if($hasError && $errorMessage)
            <p class="mt-1 text-xs text-destructive">
                {{ $errorMessage }}
            </p>
        @endif
    </div>
@endif
