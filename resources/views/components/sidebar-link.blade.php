@props(['active' => false, 'href'])

<a href="{{ $href }}" wire:navigate {{ $attributes->class([
    'flex items-center gap-3 px-3 py-2.5 rounded-md transition-colors',
    'bg-accent text-black font-medium' => $active,
    'text-muted hover:text-foreground hover:bg-card-hover' => !$active,
]) }}>
    <span class="{{ $active ? 'text-black' : 'text-current' }}">
        {{ $icon }}
    </span>
    <span>{{ $slot }}</span>
</a>
