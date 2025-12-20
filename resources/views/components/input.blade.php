@props([
    'label' => null,
    'error' => null,
    'id' => null,
    'inline' => false,
    'required' => false,
])

@php
$wireModel = $attributes->wire('model')->value();
$inputId = $id ?? $wireModel ?? 'input-' . uniqid();
$errorKey = $wireModel ?? $inputId;
$hasError = $error !== null || $errors->has($errorKey);
$errorMessage = $error ?? $errors->first($errorKey);
$inputClasses = 'w-full bg-input border rounded-lg px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-colors ' .
    ($hasError ? 'border-destructive focus:ring-destructive' : 'border-border');
@endphp

@if($inline)
    <input
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => $inputClasses]) }}
    >
@else
    <div class="w-full">
        @if($label)
            <label for="{{ $inputId }}" class="block text-sm font-medium text-muted mb-1">
                {{ $label }}@if($required) <span class="text-destructive">*</span>@endif
            </label>
        @endif

        <input
            id="{{ $inputId }}"
            {{ $attributes->merge(['class' => $inputClasses]) }}
        >

        @if($hasError && $errorMessage)
            <p class="mt-1 text-xs text-red-400">
                {{ $errorMessage }}
            </p>
        @endif
    </div>
@endif
