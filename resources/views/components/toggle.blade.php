@props([
    'label' => null,
    'description' => null,
    'id' => null,
    'size' => 'default',
    'disabled' => false,
    'checked' => false,
])

@php
$inputId = $id ?? 'toggle-' . uniqid();
$wireModel = $attributes->wire('model')->value();
$hasCustomClick = $attributes->has('x-on:click') || $attributes->has('@click');

// Size variants
$sizeClasses = match($size) {
    'sm' => 'w-10 h-6',
    'default' => 'w-12 h-7',
    default => 'w-12 h-7',
};

$circleClasses = match($size) {
    'sm' => 'w-4 h-4',
    'default' => 'w-5 h-5',
    default => 'w-5 h-5',
};

$translateChecked = match($size) {
    'sm' => 'translate-x-4',
    'default' => 'translate-x-5',
    default => 'translate-x-5',
};
@endphp

<div class="flex items-center justify-between">
    @if($label || $description)
        <div>
            @if($label)
                <p class="text-sm font-medium text-foreground">{{ $label }}</p>
            @endif
            @if($description)
                <p class="text-xs text-muted">{{ $description }}</p>
            @endif
        </div>
    @endif

    <button
        type="button"
        role="switch"
        id="{{ $inputId }}"
        @if($disabled)
            disabled
        @endif
        @if($wireModel)
            x-data="{ checked: @entangle($attributes->wire('model')) }"
            @if(!$hasCustomClick)
                @click="checked = !checked"
            @endif
            :aria-checked="checked"
            :class="checked ? 'bg-accent' : 'bg-border'"
        @elseif($hasCustomClick)
            x-data="{ checked: @json($checked) }"
            :aria-checked="checked.toString()"
            :class="checked ? 'bg-accent' : 'bg-border'"
        @else
            x-data="{ checked: @json($checked) }"
            @click="checked = !checked; $dispatch('input', checked)"
            :aria-checked="checked.toString()"
            :class="checked ? 'bg-accent' : 'bg-border'"
        @endif
        {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'relative ' . $sizeClasses . ' rounded-full transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-background disabled:opacity-50 disabled:cursor-not-allowed']) }}
    >
        <span
            :class="checked ? '{{ $translateChecked }}' : 'translate-x-0'"
            class="absolute top-1 left-1 {{ $circleClasses }} bg-white rounded-full transition-transform shadow-sm"
        ></span>
    </button>
</div>
