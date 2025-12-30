@props([
    'href' => null,
    'label' => '',
    'prefetch' => false,
])

@php
    $tag = $href ? 'a' : 'button';
    $baseClasses = 'flex flex-col items-center justify-center flex-1 py-1 cursor-pointer';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @php echo $prefetch ? 'wire:navigate.hover' : 'wire:navigate'; @endphp @else type="button" @endif
    {{ $attributes->class([$baseClasses]) }}
>
    <span class="flex items-center justify-center w-14 h-14 -mt-6 bg-accent text-background rounded-full shadow-lg shadow-accent/30 hover:bg-accent-hover transition-colors">
        {{ $slot }}
    </span>
    <span class="text-xs font-medium text-accent mt-1">{{ $label }}</span>
</{{ $tag }}>
