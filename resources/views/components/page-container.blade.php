@props(['mobileFullWidth' => false])

<div {{ $attributes->merge(['class' => $mobileFullWidth ? 'p-0 md:p-4 lg:p-6' : 'p-4 sm:p-6']) }}>
    {{ $slot }}
</div>
