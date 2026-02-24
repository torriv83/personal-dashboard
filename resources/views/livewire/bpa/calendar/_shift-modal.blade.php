{{-- Modal: Opprett/Rediger vakt (Pure Alpine.js) --}}
<div
    x-show="modal.show"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="if (modal.show && !recurringDialog.show) closeModal()"
    class="fixed inset-0 z-50 overflow-y-auto"
>
    {{-- Backdrop --}}
    <div
        @click="closeModal()"
        class="fixed inset-0 bg-background/90 backdrop-blur-sm cursor-pointer"
    ></div>

    {{-- Modal innhold --}}
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div
            x-show="modal.show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            @click.stop
            class="relative w-full max-w-md bg-card border border-border rounded-xl shadow-2xl"
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-border">
                <h2 class="text-lg font-semibold text-foreground"
                    x-text="modal.isEditing ? 'Rediger vakt' : 'Opprett vakt'"></h2>
                <button
                    @click="closeModal()"
                    class="p-1.5 rounded-lg text-muted hover:text-foreground hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-5 space-y-5">
                {{-- Validation errors (general) --}}
                <template x-if="Object.keys(modal.errors).length > 0">
                    <div class="p-3 bg-destructive/10 border border-destructive/30 rounded-lg">
                        <ul class="text-sm text-destructive space-y-1">
                            <template x-for="(fieldErrors, field) in modal.errors" :key="field">
                                <template x-for="(err, idx) in fieldErrors" :key="field + idx">
                                    <li x-text="err"></li>
                                </template>
                            </template>
                        </ul>
                    </div>
                </template>

                {{-- Assistent-seksjon --}}
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-foreground">Assistent</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Velg hvem som skal jobbe</p>
                    </div>

                    {{-- Assistent dropdown --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">
                            Hvem <span class="text-destructive">*</span>
                        </label>
                        <div class="relative">
                            <select
                                x-model="modal.form.assistant_id"
                                class="w-full bg-input border border-border rounded-lg px-3 py-2.5 text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-colors appearance-none cursor-pointer"
                            >
                                <option value="">Velg assistent...</option>
                                <template x-for="assistant in assistants" :key="'modal-a-' + assistant.id">
                                    <option :value="assistant.id" x-text="assistant.name"></option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <template x-if="modal.errors.assistant_id">
                            <p class="text-destructive text-xs mt-1" x-text="modal.errors.assistant_id[0]"></p>
                        </template>
                    </div>

                    {{-- Checkboxes --}}
                    <div class="flex items-center gap-6">
                        <label class="inline-flex items-center gap-2.5 cursor-pointer group">
                            <input
                                type="checkbox"
                                x-model="modal.form.is_unavailable"
                                class="w-5 h-5 rounded border-border text-destructive focus:ring-destructive/50 cursor-pointer"
                            >
                            <span class="text-sm text-foreground select-none">Ikke tilgjengelig</span>
                        </label>

                        <label class="inline-flex items-center gap-2.5 cursor-pointer group">
                            <input
                                type="checkbox"
                                x-model="modal.form.is_all_day"
                                class="w-5 h-5 rounded border-border text-accent focus:ring-accent/50 cursor-pointer"
                            >
                            <span class="text-sm text-foreground select-none">Hele dagen</span>
                        </label>
                    </div>

                    {{-- Eksisterende gjentakende indikator --}}
                    <div
                        x-show="modal.isExistingRecurring && modal.isEditing"
                        x-cloak
                        class="mt-4 p-3 bg-destructive/10 rounded-lg border border-destructive/30"
                    >
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-destructive shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span class="text-sm font-medium text-destructive">Del av gjentakende serie</span>
                        </div>
                        <p class="text-xs text-muted mt-1.5">
                            Endringer i denne vakten kan påvirke andre vakter i serien.
                        </p>
                    </div>

                    {{-- Gjentakende (kun for "Ikke tilgjengelig" og ikke allerede gjentakende) --}}
                    <div
                        x-show="modal.form.is_unavailable && !modal.isExistingRecurring"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-2"
                        class="mt-4 p-4 bg-card-hover rounded-lg border border-border space-y-4"
                    >
                        <label class="inline-flex items-center gap-2.5 cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="modal.form.is_recurring"
                                class="w-5 h-5 rounded border-border text-accent focus:ring-accent/50 cursor-pointer"
                            >
                            <span class="text-sm font-medium text-foreground select-none">Gjentakende</span>
                        </label>

                        <div x-show="modal.form.is_recurring" x-cloak class="space-y-4 pt-2">
                            <template x-if="modal.isEditing">
                                <p class="text-xs text-muted-foreground">
                                    Oppretter nye gjentakende oppføringer basert på denne vakten.
                                </p>
                            </template>

                            {{-- Intervall --}}
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1.5">Gjenta</label>
                                <select
                                    x-model="modal.form.recurring_interval"
                                    class="w-full bg-input border border-border rounded-lg px-3 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-colors appearance-none cursor-pointer"
                                >
                                    <option value="weekly">Ukentlig</option>
                                    <option value="biweekly">Hver 2. uke</option>
                                    <option value="monthly">Månedlig</option>
                                </select>
                            </div>

                            {{-- Avslutt --}}
                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-foreground">Avslutt</label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        x-model="modal.form.recurring_end_type"
                                        value="count"
                                        class="w-4 h-4 text-accent focus:ring-accent/50 cursor-pointer"
                                    >
                                    <span class="text-sm text-foreground">Etter</span>
                                    <input
                                        type="number"
                                        x-model.number="modal.form.recurring_count"
                                        min="1"
                                        max="52"
                                        class="w-16 bg-input border border-border rounded-lg px-2 py-1 text-foreground text-center focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent"
                                        :disabled="modal.form.recurring_end_type !== 'count'"
                                    >
                                    <span class="text-sm text-foreground">ganger</span>
                                </label>

                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        x-model="modal.form.recurring_end_type"
                                        value="date"
                                        class="w-4 h-4 text-accent focus:ring-accent/50 cursor-pointer"
                                    >
                                    <span class="text-sm text-foreground">På dato</span>
                                    <input
                                        type="date"
                                        x-model="modal.form.recurring_end_date"
                                        class="bg-input border border-border rounded-lg px-2 py-1 text-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent cursor-pointer"
                                        :disabled="modal.form.recurring_end_type !== 'date'"
                                    >
                                </label>
                            </div>

                            {{-- Forhåndsvisning --}}
                            <template x-if="getRecurringPreviewDates().length > 0">
                                <div class="pt-2 border-t border-border">
                                    <p class="text-xs text-muted mb-2">
                                        Forhåndsvisning:
                                        <span x-text="(modal.isEditing ? getRecurringPreviewDates().slice(1) : getRecurringPreviewDates()).length"></span>
                                        <span x-show="modal.isEditing">nye</span>
                                        oppføringer
                                    </p>
                                    <div class="flex flex-wrap gap-1.5">
                                        <template x-for="(date, idx) in (modal.isEditing ? getRecurringPreviewDates().slice(1) : getRecurringPreviewDates()).slice(0, 8)" :key="'preview-' + idx">
                                            <span class="text-xs px-2 py-1 bg-destructive/20 text-destructive rounded"
                                                x-text="formatDateShort(date)"></span>
                                        </template>
                                        <template x-if="(modal.isEditing ? getRecurringPreviewDates().slice(1) : getRecurringPreviewDates()).length > 8">
                                            <span class="text-xs px-2 py-1 bg-muted/20 text-muted rounded"
                                                x-text="'+' + ((modal.isEditing ? getRecurringPreviewDates().slice(1) : getRecurringPreviewDates()).length - 8) + ' til'"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="modal.isEditing && getRecurringPreviewDates().slice(1).length === 0 && modal.form.is_recurring">
                                <div class="pt-2 border-t border-border">
                                    <p class="text-xs text-muted">
                                        Velg flere ganger for å opprette nye oppføringer
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Separator --}}
                <div class="border-t border-border"></div>

                {{-- Tid-seksjon --}}
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-medium text-foreground">Tid</h3>
                        <p class="text-xs text-muted-foreground mt-0.5">Velg start og slutt</p>
                    </div>

                    {{-- Dato og tid --}}
                    <div class="grid grid-cols-2 gap-3">
                        {{-- Fra dato --}}
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">
                                Fra <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2.5 text-foreground cursor-pointer">
                                    <span
                                        x-text="modal.form.from_date ? new Date(modal.form.from_date + 'T00:00:00').toLocaleDateString('nb-NO', { day: '2-digit', month: '2-digit', year: 'numeric' }) : 'Velg dato...'"
                                        :class="modal.form.from_date ? 'text-foreground' : 'text-muted'"
                                    ></span>
                                    <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input
                                    type="date"
                                    x-model="modal.form.from_date"
                                    class="datepicker-overlay"
                                >
                            </div>
                            <template x-if="modal.errors.from_date">
                                <p class="text-destructive text-xs mt-1" x-text="modal.errors.from_date[0]"></p>
                            </template>
                        </div>

                        {{-- Fra tid --}}
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">
                                Kl <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2.5 cursor-pointer transition-colors"
                                    :class="modal.form.is_all_day ? 'opacity-50 cursor-not-allowed' : ''"
                                >
                                    <span
                                        x-text="modal.form.from_time || 'Velg tid...'"
                                        :class="modal.form.from_time ? 'text-foreground' : 'text-muted'"
                                    ></span>
                                    <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="time"
                                    x-model="modal.form.from_time"
                                    :disabled="modal.form.is_all_day"
                                    class="datepicker-overlay disabled:cursor-not-allowed"
                                >
                            </div>
                            <template x-if="modal.errors.from_time">
                                <p class="text-destructive text-xs mt-1" x-text="modal.errors.from_time[0]"></p>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        {{-- Til dato --}}
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">
                                Til <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2.5 text-foreground cursor-pointer">
                                    <span
                                        x-text="modal.form.to_date ? new Date(modal.form.to_date + 'T00:00:00').toLocaleDateString('nb-NO', { day: '2-digit', month: '2-digit', year: 'numeric' }) : 'Velg dato...'"
                                        :class="modal.form.to_date ? 'text-foreground' : 'text-muted'"
                                    ></span>
                                    <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <input
                                    type="date"
                                    x-model="modal.form.to_date"
                                    class="datepicker-overlay"
                                >
                            </div>
                            <template x-if="modal.errors.to_date">
                                <p class="text-destructive text-xs mt-1" x-text="modal.errors.to_date[0]"></p>
                            </template>
                        </div>

                        {{-- Til tid --}}
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">
                                Kl <span class="text-destructive">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="flex items-center justify-between w-full bg-input border border-border rounded-lg px-3 py-2.5 cursor-pointer transition-colors"
                                    :class="modal.form.is_all_day ? 'opacity-50 cursor-not-allowed' : ''"
                                >
                                    <span
                                        x-text="modal.form.to_time || 'Velg tid...'"
                                        :class="modal.form.to_time ? 'text-foreground' : 'text-muted'"
                                    ></span>
                                    <svg class="w-5 h-5 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <input
                                    type="time"
                                    x-model="modal.form.to_time"
                                    :disabled="modal.form.is_all_day"
                                    class="datepicker-overlay disabled:cursor-not-allowed"
                                >
                            </div>
                            <template x-if="modal.errors.to_time">
                                <p class="text-destructive text-xs mt-1" x-text="modal.errors.to_time[0]"></p>
                            </template>
                        </div>
                    </div>

                    {{-- Total tid --}}
                    <div class="flex items-center justify-between py-3 px-4 bg-card-hover rounded-lg">
                        <span class="text-sm text-muted">Total tid</span>
                        <span
                            class="text-sm font-semibold"
                            :class="totalTime === 'Ugyldig tid' ? 'text-destructive' : 'text-accent'"
                            x-text="totalTime || '-'"
                        ></span>
                    </div>

                    {{-- Notat --}}
                    <div>
                        <label class="block text-sm font-medium text-foreground mb-1.5">
                            Notat
                        </label>
                        <textarea
                            x-model="modal.form.note"
                            rows="2"
                            placeholder="Valgfritt notat..."
                            class="w-full bg-input border border-border rounded-lg px-3 py-2.5 text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-accent focus:border-accent transition-colors resize-none"
                        ></textarea>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between px-5 py-4 border-t border-border">
                {{-- Slett/Arkiver-knapper (kun for eksisterende vakter) --}}
                <div class="flex items-center gap-2">
                    <template x-if="modal.isEditing && modal.editingShiftId">
                        <div class="flex items-center gap-2">
                            <button
                                @click="archiveShift(modal.editingShiftId)"
                                :disabled="modal.isSubmitting"
                                class="px-4 py-2 text-sm font-medium text-warning hover:text-white hover:bg-warning rounded-lg transition-colors cursor-pointer disabled:opacity-50"
                                title="Arkiver (kan gjenopprettes)"
                            >
                                Arkiver
                            </button>
                            <button
                                @click="deleteShift(modal.editingShiftId)"
                                :disabled="modal.isSubmitting"
                                class="px-4 py-2 text-sm font-medium text-destructive hover:text-white hover:bg-destructive rounded-lg transition-colors cursor-pointer disabled:opacity-50"
                                title="Slett permanent"
                            >
                                Slett
                            </button>
                        </div>
                    </template>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        @click="closeModal()"
                        class="px-4 py-2 text-sm font-medium text-muted hover:text-foreground transition-colors cursor-pointer"
                    >
                        Avbryt
                    </button>
                    <template x-if="!modal.isEditing">
                        <button
                            @click="saveShift(true)"
                            :disabled="modal.isSubmitting"
                            class="px-4 py-2 text-sm font-medium text-foreground bg-card-hover border border-border rounded-lg hover:bg-input transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <span x-show="!modal.isSubmitting">Opprett & ny</span>
                            <span x-show="modal.isSubmitting" x-cloak>Lagrer...</span>
                        </button>
                    </template>
                    <button
                        @click="saveShift()"
                        :disabled="modal.isSubmitting"
                        class="px-5 py-2 text-sm font-semibold text-black bg-accent rounded-lg hover:bg-accent-hover transition-colors cursor-pointer disabled:opacity-50"
                    >
                        <span x-show="!modal.isSubmitting" x-text="modal.isEditing ? 'Lagre' : 'Opprett'"></span>
                        <span x-show="modal.isSubmitting" x-cloak>Lagrer...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recurring Action Dialog (Pure Alpine.js) --}}
<div
    x-show="recurringDialog.show"
    x-cloak
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="recurringDialog.show = false"
    class="fixed inset-0 z-[60] overflow-y-auto"
>
    {{-- Backdrop --}}
    <div
        @click="recurringDialog.show = false"
        class="fixed inset-0 bg-background/95 backdrop-blur-sm cursor-pointer"
    ></div>

    {{-- Dialog --}}
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div
            x-show="recurringDialog.show"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            class="relative w-full max-w-sm bg-card border border-border rounded-xl shadow-2xl"
        >
            {{-- Header --}}
            <div class="px-5 py-4 border-b border-border">
                <h3 class="text-lg font-semibold text-foreground">
                    <span x-show="recurringDialog.action === 'delete'">Slett gjentakende oppføring</span>
                    <span x-show="recurringDialog.action === 'archive'">Arkiver gjentakende oppføring</span>
                    <span x-show="recurringDialog.action === 'move'">Flytt gjentakende oppføring</span>
                    <span x-show="recurringDialog.action === 'edit'">Rediger gjentakende oppføring</span>
                </h3>
                <p class="text-sm text-muted mt-1">
                    Denne oppføringen er del av en gjentakende serie.
                </p>
            </div>

            {{-- Options --}}
            <div class="p-5 space-y-3">
                <button
                    @click="confirmRecurring('single')"
                    class="w-full text-left px-4 py-3 rounded-lg border border-border hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <span class="block text-sm font-medium text-foreground">Kun denne</span>
                    <span class="block text-xs text-muted mt-0.5">
                        <span x-show="recurringDialog.action === 'delete'">Slett</span>
                        <span x-show="recurringDialog.action === 'archive'">Arkiver</span>
                        <span x-show="recurringDialog.action === 'move'">Flytt</span>
                        <span x-show="recurringDialog.action === 'edit'">Endre</span>
                        bare denne ene oppføringen
                    </span>
                </button>

                <button
                    @click="confirmRecurring('future')"
                    class="w-full text-left px-4 py-3 rounded-lg border border-border hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <span class="block text-sm font-medium text-foreground">Denne og fremtidige</span>
                    <span class="block text-xs text-muted mt-0.5">
                        <span x-show="recurringDialog.action === 'delete'">Slett</span>
                        <span x-show="recurringDialog.action === 'archive'">Arkiver</span>
                        <span x-show="recurringDialog.action === 'move'">Flytt</span>
                        <span x-show="recurringDialog.action === 'edit'">Endre</span>
                        denne og alle fremtidige i serien
                    </span>
                </button>

                <button
                    @click="confirmRecurring('all')"
                    class="w-full text-left px-4 py-3 rounded-lg border border-border hover:bg-card-hover transition-colors cursor-pointer"
                >
                    <span class="block text-sm font-medium text-foreground">Alle i serien</span>
                    <span class="block text-xs text-muted mt-0.5">
                        <span x-show="recurringDialog.action === 'delete'">Slett</span>
                        <span x-show="recurringDialog.action === 'archive'">Arkiver</span>
                        <span x-show="recurringDialog.action === 'move'">Flytt</span>
                        <span x-show="recurringDialog.action === 'edit'">Endre</span>
                        alle oppføringer i denne serien
                    </span>
                </button>
            </div>

            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-border">
                <button
                    @click="recurringDialog.show = false"
                    class="w-full px-4 py-2 text-sm font-medium text-muted hover:text-foreground transition-colors cursor-pointer"
                >
                    Avbryt
                </button>
            </div>
        </div>
    </div>
</div>
