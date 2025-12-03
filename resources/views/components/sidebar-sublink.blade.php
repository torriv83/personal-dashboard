@props(['active' => false, 'href'])

<a
    href="{{ $href }}"
    @class([
        'block px-3 py-2 rounded-md text-sm transition-colors',
        'bg-accent text-black font-medium' => $active,
        'text-muted hover:text-foreground hover:bg-card-hover' => !$active,
    ])
>
    {{ $slot }}
</a>
