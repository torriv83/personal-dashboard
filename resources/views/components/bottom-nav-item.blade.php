@props([
    'href' => '#',
    'label' => '',
    'active' => false,
    'prefetch' => false,
])

<a href="{{ $href }}" @php echo $prefetch ? 'wire:navigate.hover' : 'wire:navigate'; @endphp {{ $attributes->class([
        'flex flex-col items-center justify-center flex-1 py-2 transition-colors cursor-pointer',
        'text-accent' => $active,
        'text-muted hover:text-foreground' => !$active,
    ]) }}>
    <span class="mb-1">
        {{ $slot }}
    </span>
    <span class="text-xs font-medium">{{ $label }}</span>
</a>
