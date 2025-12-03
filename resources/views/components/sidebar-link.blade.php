@props(['active' => false, 'href'])

@php
$classes = $active
    ? 'flex items-center gap-3 px-3 py-2.5 rounded-md bg-accent text-black font-medium transition-colors'
    : 'flex items-center gap-3 px-3 py-2.5 rounded-md text-muted hover:text-foreground hover:bg-card-hover transition-colors';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    <span class="{{ $active ? 'text-black' : 'text-current' }}">
        {{ $icon }}
    </span>
    <span>{{ $slot }}</span>
</a>
