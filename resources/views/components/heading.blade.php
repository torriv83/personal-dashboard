@props([
    'level' => 1,
    'subtitle' => null,
])

@php
$tag = 'h' . $level;
$classes = match($level) {
    1 => 'text-4xl font-bold text-foreground tracking-tight',
    2 => 'text-3xl font-semibold text-foreground tracking-tight',
    3 => 'text-2xl font-semibold text-foreground',
    4 => 'text-xl font-semibold text-foreground',
    default => 'text-4xl font-bold text-foreground tracking-tight',
};
@endphp

<div {{ $attributes->only('class') }}>
    <{{ $tag }} {{ $attributes->except('class')->merge(['class' => $classes]) }}>
        {{ $slot }}
    </{{ $tag }}>

    @if($subtitle)
        <p class="mt-2 text-muted-foreground">
            {{ $subtitle }}
        </p>
    @endif
</div>
