@props([
    'label' => null,
    'error' => null,
    'id' => null,
    'rows' => 3,
])

@php
$wireModel = $attributes->wire('model')->value();
$inputId = $id ?? $wireModel ?? 'textarea-' . uniqid();
$errorKey = $wireModel ?? $inputId;
$hasError = $error !== null || $errors->has($errorKey);
$errorMessage = $error ?? $errors->first($errorKey);
$textareaClasses = 'w-full bg-input border rounded-lg px-3 py-2 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent transition-colors resize-none ' .
    ($hasError ? 'border-destructive focus:ring-destructive' : 'border-border');
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-muted mb-1">
            {{ $label }}
        </label>
    @endif

    <textarea
        id="{{ $inputId }}"
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => $textareaClasses]) }}
    >{{ $slot }}</textarea>

    @if($hasError && $errorMessage)
        <p class="mt-1 text-xs text-red-400">
            {{ $errorMessage }}
        </p>
    @endif
</div>
