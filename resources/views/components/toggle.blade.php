@props([
    'label' => null,
    'description' => null,
    'id' => null,
])

@php
$inputId = $id ?? 'toggle-' . uniqid();
$wireModel = $attributes->wire('model')->value();
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
        @if($wireModel)
            x-data="{ checked: @entangle($attributes->wire('model')) }"
            @click="checked = !checked"
            :aria-checked="checked"
            :class="checked ? 'bg-accent' : 'bg-border'"
        @else
            x-data="{ checked: false }"
            x-init="checked = $el.getAttribute('aria-checked') === 'true'"
            @click="checked = !checked; $dispatch('input', checked)"
            :aria-checked="checked"
            :class="checked ? 'bg-accent' : 'bg-border'"
        @endif
        {{ $attributes->whereDoesntStartWith('wire:model')->merge(['class' => 'relative w-12 h-7 rounded-full transition-colors cursor-pointer focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2 focus:ring-offset-background']) }}
    >
        <span
            :class="checked ? 'translate-x-5' : 'translate-x-0'"
            class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full transition-transform shadow-sm"
        ></span>
    </button>
</div>
