@props(['iconType', 'size' => 'w-6 h-6'])

@switch($iconType)
    @case('clearsky')
        <svg class="{{ $size }} text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z" />
        </svg>
        @break
    @case('clearsky-night')
        <svg class="{{ $size }} text-blue-300" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd" />
        </svg>
        @break
    @case('fair')
    @case('partlycloudy')
        <svg class="{{ $size }} text-accent" fill="currentColor" viewBox="0 0 24 24">
            <path d="M4.5 12a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.03-4.06a.75.75 0 101.06-1.061L6.53 5.818a.75.75 0 10-1.06 1.06l1.06 1.061zM9 3a.75.75 0 00-1.5 0v1.5a.75.75 0 001.5 0V3zm5.47 2.818a.75.75 0 10-1.06 1.06l1.06 1.061a.75.75 0 001.06-1.06l-1.06-1.06zM16.5 9a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" />
            <path fill-rule="evenodd" d="M6.75 17.25A2.25 2.25 0 019 15h9a3 3 0 100-6h-.35a4.5 4.5 0 00-8.4 1.5H9a2.25 2.25 0 00-2.25 2.25v4.5z" clip-rule="evenodd" />
        </svg>
        @break
    @case('fair-night')
    @case('partlycloudy-night')
        <svg class="{{ $size }} text-blue-300" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a4 4 0 001 2M18 12a3 3 0 11-6 0h-.35a4.5 4.5 0 00-8.4 1.5H3a2.25 2.25 0 00-2.25 2.25v1.5A2.25 2.25 0 003 19.5h12a3 3 0 100-6h-.35z" clip-rule="evenodd" />
        </svg>
        @break
    @case('cloudy')
        <svg class="{{ $size }} text-gray-400" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M4.5 9.75a6 6 0 0111.573-2.226 3.75 3.75 0 014.133 4.303A4.5 4.5 0 0118 20.25H6.75a5.25 5.25 0 01-2.23-10.004 6.072 6.072 0 01-.02-.496z" clip-rule="evenodd" />
        </svg>
        @break
    @case('rain')
        <svg class="{{ $size }} text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19.5v2M12 19.5v2M15 19.5v2" />
        </svg>
        @break
    @case('snow')
        <svg class="{{ $size }} text-blue-200" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18l-3 3m3-3l3 3m-3 15l-3-3m3 3l3-3M3 12h18M3 12l3-3m-3 3l3 3m15-3l-3-3m3 3l-3 3" />
        </svg>
        @break
    @case('sleet')
        <svg class="{{ $size }} text-blue-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 19l1 2M12 19v2M16 19l-1 2" />
        </svg>
        @break
    @case('thunder')
        <svg class="{{ $size }} text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M14.615 1.595a.75.75 0 01.359.852L12.982 9.75h7.268a.75.75 0 01.548 1.262l-10.5 11.25a.75.75 0 01-1.272-.71l1.992-7.302H3.75a.75.75 0 01-.548-1.262l10.5-11.25a.75.75 0 01.913-.143z" clip-rule="evenodd" />
        </svg>
        @break
    @case('fog')
        <svg class="{{ $size }} text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 15h18M3 12h18M3 9h18" />
        </svg>
        @break
    @default
        <svg class="{{ $size }} text-gray-400" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M4.5 9.75a6 6 0 0111.573-2.226 3.75 3.75 0 014.133 4.303A4.5 4.5 0 0118 20.25H6.75a5.25 5.25 0 01-2.23-10.004 6.072 6.072 0 01-.02-.496z" clip-rule="evenodd" />
        </svg>
@endswitch
