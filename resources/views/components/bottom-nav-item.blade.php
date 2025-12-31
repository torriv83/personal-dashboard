@props([
    'href' => null,
    'label' => '',
    'active' => false,
    'prefetch' => false,
])

@php
    $tag = $href ? 'a' : 'button';
    $baseClasses = 'flex flex-col items-center justify-center flex-1 py-2 transition-colors cursor-pointer';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @php echo $prefetch ? 'wire:navigate.hover' : 'wire:navigate'; @endphp @else type="button" @endif
    {{ $attributes->class([
        $baseClasses,
        'text-accent' => $active,
        'text-muted hover:text-foreground' => !$active,
    ]) }}
>
    <span class="mb-1">
        {{ $slot }}
    </span>
    <span class="text-xs font-medium">{{ $label }}</span>
</{{ $tag }}>
