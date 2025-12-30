@props(['active' => false, 'href' => null, 'disabled' => false, 'prefetch' => false])

@if($disabled)
    <span
        @class([
            'block px-3 py-2 rounded-md text-sm cursor-not-allowed opacity-50',
            'text-muted',
        ])
    >
        {{ $slot }}
    </span>
@else
    <a href="{{ $href }}" @php echo $prefetch ? 'wire:navigate.hover' : 'wire:navigate'; @endphp {{ $attributes->class([
            'block px-3 py-2 rounded-md text-sm transition-colors cursor-pointer',
            'bg-accent text-black font-medium' => $active,
            'text-muted hover:text-foreground hover:bg-card-hover' => !$active,
        ]) }}>
        {{ $slot }}
    </a>
@endif
