@props([
    'label' => null,
    'description' => null,
    'id' => null,
    'size' => 'md',
    'color' => 'accent',
])

@php
$inputId = $id ?? 'checkbox-' . uniqid();

$sizeClasses = match($size) {
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
    default => 'w-5 h-5',
};

$colorClasses = match($color) {
    'accent' => 'text-accent focus:ring-accent',
    'warning' => 'text-warning focus:ring-warning',
    'destructive' => 'text-destructive focus:ring-destructive',
    default => 'text-accent focus:ring-accent',
};

$checkboxClasses = $sizeClasses . ' rounded border-border bg-input ' . $colorClasses . ' focus:ring-2 focus:ring-offset-2 focus:ring-offset-card cursor-pointer';
@endphp

<label for="{{ $inputId }}" class="inline-flex items-center gap-3 cursor-pointer">
    <input
        type="checkbox"
        id="{{ $inputId }}"
        {{ $attributes->merge(['class' => $checkboxClasses]) }}
    >
    @if($label || $description)
        <div>
            @if($label)
                <span class="text-sm font-medium text-foreground select-none">{{ $label }}</span>
            @endif
            @if($description)
                <p class="text-xs text-muted-foreground">{{ $description }}</p>
            @endif
        </div>
    @endif
</label>
