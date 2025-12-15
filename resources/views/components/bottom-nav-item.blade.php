@props([
    'href' => '#',
    'label' => '',
    'active' => false,
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'flex flex-col items-center justify-center flex-1 py-2 transition-colors cursor-pointer',
        'text-accent' => $active,
        'text-muted hover:text-foreground' => !$active,
    ]) }}
>
    <span class="mb-1">
        {{ $slot }}
    </span>
    <span class="text-xs font-medium">{{ $label }}</span>
</a>
