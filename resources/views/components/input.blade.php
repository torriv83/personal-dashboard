@props([
    'label' => null,
    'error' => null,
    'id' => null,
])

@php
$wireModel = $attributes->wire('model')->value();
$inputId = $id ?? $wireModel ?? 'input-' . uniqid();
$errorKey = $wireModel ?? $inputId;
$hasError = $error !== null || $errors->has($errorKey);
$errorMessage = $error ?? $errors->first($errorKey);
$inputClasses = 'w-full bg-input border rounded-md px-3 py-2 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background transition-colors ' .
    ($hasError ? 'border-destructive focus:ring-destructive' : 'border-border focus:ring-accent');
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-foreground mb-1.5">
            {{ $label }}
        </label>
    @endif

    <input
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => $inputClasses]) }}
    >

    @if($hasError && $errorMessage)
        <p class="mt-1.5 text-sm text-destructive">
            {{ $errorMessage }}
        </p>
    @endif
</div>
