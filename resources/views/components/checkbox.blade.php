@props([
    'label' => null,
    'id' => null,
])

@php
$inputId = $id ?? 'checkbox-' . uniqid();
@endphp

<label for="{{ $inputId }}" class="inline-flex items-center gap-2 cursor-pointer group">
    <div class="relative">
        <input
            type="checkbox"
            id="{{ $inputId }}"
            {{ $attributes->merge(['class' => 'peer sr-only']) }}
        >
        <div class="w-5 h-5 bg-input border border-border rounded transition-colors peer-checked:bg-accent peer-checked:border-accent peer-focus:ring-2 peer-focus:ring-accent peer-focus:ring-offset-2 peer-focus:ring-offset-background group-hover:border-muted">
            {{-- Checkmark --}}
            <svg class="w-5 h-5 text-black opacity-0 peer-checked:opacity-100 transition-opacity absolute inset-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        {{-- Checkmark overlay (needs to be after the box for peer-checked to work) --}}
        <svg class="w-5 h-5 text-black absolute inset-0 opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
        </svg>
    </div>

    @if($label)
        <span class="text-sm text-foreground select-none">{{ $label }}</span>
    @endif
</label>
