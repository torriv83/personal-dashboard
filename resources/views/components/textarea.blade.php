@props([
    'label' => null,
    'error' => null,
    'id' => null,
    'rows' => 3,
])

@php
$inputId = $id ?? 'textarea-' . uniqid();
$hasError = $error !== null;
$textareaClasses = 'w-full bg-input border rounded-md px-3 py-2 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-background transition-colors resize-none ' .
    ($hasError ? 'border-destructive focus:ring-destructive' : 'border-border focus:ring-accent');
@endphp

<div class="w-full">
    @if($label)
        <label for="{{ $inputId }}" class="block text-sm font-medium text-foreground mb-1.5">
            {{ $label }}
        </label>
    @endif

    <textarea
        id="{{ $inputId }}"
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => $textareaClasses]) }}
    >{{ $slot }}</textarea>

    @if($error)
        <p class="mt-1.5 text-sm text-destructive">
            {{ $error }}
        </p>
    @endif
</div>
