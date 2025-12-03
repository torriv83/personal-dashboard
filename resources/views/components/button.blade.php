@props([
    'variant' => 'primary',
    'size' => 'md',
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium rounded-md transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:opacity-50 disabled:pointer-events-none disabled:cursor-not-allowed';

$variantClasses = match($variant) {
    'primary' => 'bg-accent text-black hover:bg-accent-hover',
    'secondary' => 'bg-card-hover text-foreground border border-border hover:bg-input',
    'danger' => 'bg-destructive text-white hover:opacity-90',
    'ghost' => 'text-foreground hover:bg-card-hover',
    default => 'bg-accent text-black hover:bg-accent-hover',
};

$sizeClasses = match($size) {
    'sm' => 'h-8 px-3 text-sm',
    'md' => 'h-10 px-4',
    'lg' => 'h-12 px-6 text-lg',
    default => 'h-10 px-4',
};

$classes = $baseClasses . ' ' . $variantClasses . ' ' . $sizeClasses;
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>
    {{ $slot }}
</button>
