@props(['variant' => 'default'])

@php
$variantClasses = match($variant) {
    'success' => 'bg-success/10 text-success border-success/20',
    'warning' => 'bg-warning/10 text-warning border-warning/20',
    'danger' => 'bg-destructive/10 text-destructive border-destructive/20',
    'info' => 'bg-info/10 text-info border-info/20',
    default => 'bg-card-hover text-foreground border-border',
};

$classes = 'inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium border ' . $variantClasses;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
