@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'bg-card border border-border rounded-lg ' . ($padding ? 'p-6' : '')]) }}>
    {{ $slot }}
</div>
