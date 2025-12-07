{{--
    Toast Notification Component

    Usage:
    1. Livewire event: $this->dispatch('toast', type: 'success', message: 'Lagret!');
    2. Session flash: session()->flash('success', 'Data oppdatert');

    Types: success, error, warning, info
--}}
<div
    wire:ignore
    x-data="{
        toasts: [],
        counter: 0,
        add(toast) {
            this.counter++
            const id = this.counter
            this.toasts.push({ id, ...toast })
            setTimeout(() => this.remove(id), toast.duration || 5000)
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id)
        }
    }"
    x-on:toast.window="add($event.detail)"
    class="fixed top-4 right-4 z-[100] flex flex-col gap-3 pointer-events-none"
>
    {{-- Session flash messages (on page load) --}}
    @if (session('success'))
        <div x-init="add({ type: 'success', message: '{{ session('success') }}' })"></div>
    @endif
    @if (session('error'))
        <div x-init="add({ type: 'error', message: '{{ session('error') }}' })"></div>
    @endif
    @if (session('warning'))
        <div x-init="add({ type: 'warning', message: '{{ session('warning') }}' })"></div>
    @endif
    @if (session('info'))
        <div x-init="add({ type: 'info', message: '{{ session('info') }}' })"></div>
    @endif
    @if (session('status'))
        <div x-init="add({ type: 'success', message: '{{ session('status') }}' })"></div>
    @endif

    {{-- Toast container --}}
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="pointer-events-auto flex items-start gap-3 min-w-[320px] max-w-md p-4 rounded-lg border shadow-lg cursor-pointer"
            :class="{
                'bg-card border-success/30': toast.type === 'success',
                'bg-card border-destructive/30': toast.type === 'error',
                'bg-card border-warning/30': toast.type === 'warning',
                'bg-card border-info/30': toast.type === 'info'
            }"
            @click="remove(toast.id)"
            role="alert"
        >
            {{-- Icon --}}
            <div
                class="shrink-0 mt-0.5"
                :class="{
                    'text-success': toast.type === 'success',
                    'text-destructive': toast.type === 'error',
                    'text-warning': toast.type === 'warning',
                    'text-info': toast.type === 'info'
                }"
            >
                {{-- Success icon --}}
                <template x-if="toast.type === 'success'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </template>

                {{-- Error icon --}}
                <template x-if="toast.type === 'error'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>

                {{-- Warning icon --}}
                <template x-if="toast.type === 'warning'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>

                {{-- Info icon --}}
                <template x-if="toast.type === 'info'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </div>

            {{-- Message --}}
            <p class="flex-1 text-sm text-foreground" x-text="toast.message"></p>

            {{-- Close button --}}
            <button
                @click.stop="remove(toast.id)"
                class="shrink-0 text-muted hover:text-foreground transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
