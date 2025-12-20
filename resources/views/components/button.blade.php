@props([
    'variant' => 'primary',
    'size' => 'md',
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-50 disabled:pointer-events-none disabled:cursor-not-allowed';

$variantClasses = match($variant) {
    'primary' => 'bg-accent text-black hover:bg-accent-hover',
    'secondary' => 'bg-card-hover text-foreground border border-border hover:bg-input',
    'danger' => 'bg-destructive text-white hover:bg-destructive/90',
    'danger-text' => 'text-destructive hover:text-white hover:bg-destructive',
    'warning-text' => 'text-warning hover:text-white hover:bg-warning',
    'ghost' => 'text-muted hover:text-foreground',
    default => 'bg-accent text-black hover:bg-accent-hover',
};

$sizeClasses = match($size) {
    'sm' => 'px-3 py-1.5 text-sm',
    'md' => 'px-4 py-2 text-sm',
    'lg' => 'px-6 py-3 text-base',
    default => 'px-4 py-2 text-sm',
};

$classes = $baseClasses . ' ' . $variantClasses . ' ' . $sizeClasses;
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
    {{ $slot }}
</button>
