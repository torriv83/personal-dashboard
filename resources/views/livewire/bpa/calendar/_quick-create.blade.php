{{-- Quick Create Popover --}}
@if($showQuickCreate)
    {{-- Usynlig backdrop for å lukke --}}
    <div
        wire:click="closeQuickCreate"
        class="fixed inset-0 z-40"
        @keydown.escape.window="$wire.closeQuickCreate()"
    ></div>

    {{-- Popover ved musepekeren --}}
    <div
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed z-50 bg-card border border-border rounded-xl shadow-2xl p-3 min-w-56"
        style="left: {{ $quickCreateX }}px; top: {{ $quickCreateY }}px;"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between mb-2 pb-2 border-b border-border">
            <div>
                <div class="text-sm font-medium text-foreground">Hurtigopprett</div>
                <div class="text-xs text-muted">
                    @php
                        $startTime = Carbon\Carbon::parse($quickCreateDate . ' ' . $quickCreateTime);
                        $endTime = $quickCreateEndTime
                            ? Carbon\Carbon::parse($quickCreateDate . ' ' . $quickCreateEndTime)
                            : $startTime->copy()->addHours(3);
                        $durationMinutes = $startTime->diffInMinutes($endTime);
                        $hours = floor($durationMinutes / 60);
                        $mins = $durationMinutes % 60;
                        $durationText = $hours > 0
                            ? ($mins > 0 ? "{$hours}t {$mins}min" : "{$hours}t")
                            : "{$mins}min";
                    @endphp
                    {{ $startTime->format('d.m') }} kl {{ $quickCreateTime }} - {{ $endTime->format('H:i') }} ({{ $durationText }})
                </div>
            </div>
            <button
                wire:click="closeQuickCreate"
                class="p-1 rounded text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Assistenter --}}
        <div class="space-y-0.5">
            @foreach($this->assistants as $assistant)
                <button
                    wire:click="quickCreateShift({{ $assistant->id }})"
                    class="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-card-hover transition-colors cursor-pointer text-left"
                >
                    <span
                        class="w-3 h-3 rounded-full shrink-0"
                        style="background-color: {{ $assistant->color ?? '#3b82f6' }}"
                    ></span>
                    <span class="text-sm text-foreground">{{ $assistant->name }}</span>
                </button>
            @endforeach
        </div>
    </div>
@endif
