<?php

use App\Livewire\Bpa\Calendar;
use App\Models\Assistant;
use App\Models\Shift;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    // Clean up
    Shift::query()->forceDelete();
    Assistant::query()->forceDelete();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    // Mock GoogleCalendarService to avoid HTTP calls
    $this->mock(GoogleCalendarService::class, function ($mock) {
        $mock->shouldReceive('getAllEvents')->andReturn(collect());
    });

    // Freeze time for predictable tests
    Carbon::setTestNow(Carbon::create(2024, 6, 15, 10, 0, 0, 'Europe/Oslo'));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('renders the calendar page', function () {
    $this->get(route('bpa.calendar'))
        ->assertOk();
});

it('renders the calendar component', function () {
    Livewire::test(Calendar::class)
        ->assertStatus(200);
});

it('initializes with current date', function () {
    Livewire::test(Calendar::class)
        ->assertSet('year', 2024)
        ->assertSet('month', 6)
        ->assertSet('day', 15)
        ->assertSet('view', 'month');
});

// Navigation tests
it('can navigate to previous month', function () {
    Livewire::test(Calendar::class)
        ->assertSet('month', 6)
        ->call('previousMonth')
        ->assertSet('month', 5)
        ->assertSet('year', 2024);
});

it('can navigate to next month', function () {
    Livewire::test(Calendar::class)
        ->assertSet('month', 6)
        ->call('nextMonth')
        ->assertSet('month', 7)
        ->assertSet('year', 2024);
});

it('handles year rollover when going to previous month from January', function () {
    Carbon::setTestNow(Carbon::create(2024, 1, 15, 10, 0, 0, 'Europe/Oslo'));

    Livewire::test(Calendar::class)
        ->assertSet('month', 1)
        ->assertSet('year', 2024)
        ->call('previousMonth')
        ->assertSet('month', 12)
        ->assertSet('year', 2023);
});

it('handles year rollover when going to next month from December', function () {
    Carbon::setTestNow(Carbon::create(2024, 12, 15, 10, 0, 0, 'Europe/Oslo'));

    Livewire::test(Calendar::class)
        ->assertSet('month', 12)
        ->assertSet('year', 2024)
        ->call('nextMonth')
        ->assertSet('month', 1)
        ->assertSet('year', 2025);
});

it('can go to today', function () {
    $component = Livewire::test(Calendar::class)
        ->call('nextMonth')
        ->call('nextMonth')
        ->assertSet('month', 8);

    $component->call('goToToday')
        ->assertSet('year', 2024)
        ->assertSet('month', 6)
        ->assertSet('day', 15);
});

it('can navigate to previous day', function () {
    Livewire::test(Calendar::class)
        ->assertSet('day', 15)
        ->call('previousDay')
        ->assertSet('day', 14);
});

it('can navigate to next day', function () {
    Livewire::test(Calendar::class)
        ->assertSet('day', 15)
        ->call('nextDay')
        ->assertSet('day', 16);
});

it('can navigate to previous week', function () {
    Livewire::test(Calendar::class)
        ->assertSet('day', 15)
        ->call('previousWeek')
        ->assertSet('day', 8);
});

it('can navigate to next week', function () {
    Livewire::test(Calendar::class)
        ->assertSet('day', 15)
        ->call('nextWeek')
        ->assertSet('day', 22);
});

it('can go to specific day', function () {
    Livewire::test(Calendar::class)
        ->call('goToDay', '2024-07-20')
        ->assertSet('year', 2024)
        ->assertSet('month', 7)
        ->assertSet('day', 20)
        ->assertSet('view', 'day');
});

it('can change view', function () {
    Livewire::test(Calendar::class)
        ->assertSet('view', 'month')
        ->call('setView', 'week')
        ->assertSet('view', 'week')
        ->call('setView', 'day')
        ->assertSet('view', 'day');
});

// Modal tests
it('can open modal for creating new shift', function () {
    Livewire::test(Calendar::class)
        ->assertSet('showModal', false)
        ->call('openModal', '2024-06-20', '10:00')
        ->assertSet('showModal', true)
        ->assertSet('fromDate', '2024-06-20')
        ->assertSet('toDate', '2024-06-20')
        ->assertSet('fromTime', '10:00')
        ->assertSet('toTime', '11:00');
});

it('can close modal', function () {
    Livewire::test(Calendar::class)
        ->call('openModal')
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false);
});

it('can edit existing shift', function () {
    $assistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 09:00'),
        'ends_at' => Carbon::parse('2024-06-20 12:00'),
        'is_unavailable' => false,
        'note' => 'Test note',
    ]);

    Livewire::test(Calendar::class)
        ->call('editShift', $shift->id)
        ->assertSet('showModal', true)
        ->assertSet('editingShiftId', $shift->id)
        ->assertSet('assistantId', $assistant->id)
        ->assertSet('fromDate', '2024-06-20')
        ->assertSet('fromTime', '09:00')
        ->assertSet('toDate', '2024-06-20')
        ->assertSet('toTime', '12:00')
        ->assertSet('note', 'Test note');
});

// Shift CRUD tests
it('can create a new shift', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Calendar::class)
        ->call('openModal', '2024-06-20', '08:00')
        ->set('assistantId', $assistant->id)
        ->set('fromDate', '2024-06-20')
        ->set('toDate', '2024-06-20')
        ->set('fromTime', '08:00')
        ->set('toTime', '12:00')
        ->call('saveShift')
        ->assertHasNoErrors()
        ->assertSet('showModal', false)
        ->assertDispatched('toast');

    $this->assertDatabaseHas('shifts', [
        'assistant_id' => $assistant->id,
        'is_unavailable' => false,
    ]);
});

it('validates assistant is required when creating shift', function () {
    Livewire::test(Calendar::class)
        ->call('openModal', '2024-06-20')
        ->set('fromDate', '2024-06-20')
        ->set('toDate', '2024-06-20')
        ->call('saveShift')
        ->assertHasErrors(['assistantId' => 'required']);
});

it('validates end date is after start date', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Calendar::class)
        ->call('openModal')
        ->set('assistantId', $assistant->id)
        ->set('fromDate', '2024-06-25')
        ->set('toDate', '2024-06-20')
        ->call('saveShift')
        ->assertHasErrors(['toDate']);
});

it('can update existing shift', function () {
    $assistant = Assistant::factory()->create();
    $newAssistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'ends_at' => Carbon::parse('2024-06-20 12:00'),
    ]);

    Livewire::test(Calendar::class)
        ->call('editShift', $shift->id)
        ->set('assistantId', $newAssistant->id)
        ->set('fromTime', '09:00')
        ->set('toTime', '14:00')
        ->call('saveShift')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $shift->refresh();
    expect($shift->assistant_id)->toBe($newAssistant->id);
    expect($shift->starts_at->format('H:i'))->toBe('09:00');
    expect($shift->ends_at->format('H:i'))->toBe('14:00');
});

it('can delete a shift', function () {
    $assistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'recurring_group_id' => null, // Ensure it's not recurring
    ]);

    Livewire::test(Calendar::class)
        ->call('editShift', $shift->id)
        ->call('confirmDeleteShift', 'single') // Call directly since non-recurring shifts bypass dialog
        ->assertDispatched('toast');

    // Delete uses forceDelete (permanent), so check it's completely removed
    $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
});

// Unavailable entries tests
it('can create unavailable entry', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Calendar::class)
        ->call('openModal', '2024-06-20')
        ->set('assistantId', $assistant->id)
        ->set('isUnavailable', true)
        ->set('fromDate', '2024-06-20')
        ->set('toDate', '2024-06-20')
        ->call('saveShift')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shifts', [
        'assistant_id' => $assistant->id,
        'is_unavailable' => true,
    ]);
});

it('can create all-day entry', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Calendar::class)
        ->call('openModal', '2024-06-20')
        ->set('assistantId', $assistant->id)
        ->set('isAllDay', true)
        ->set('fromDate', '2024-06-20')
        ->set('toDate', '2024-06-20')
        ->call('saveShift')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('shifts', [
        'assistant_id' => $assistant->id,
        'is_all_day' => true,
    ]);
});

// Conflict detection tests
it('detects conflict with unavailable entry when creating work shift', function () {
    $assistant = Assistant::factory()->create();

    // Create unavailable entry
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'ends_at' => Carbon::parse('2024-06-20 16:00'),
        'is_unavailable' => true,
    ]);

    // Try to create overlapping work shift
    Livewire::test(Calendar::class)
        ->call('openModal', '2024-06-20')
        ->set('assistantId', $assistant->id)
        ->set('isUnavailable', false)
        ->set('fromDate', '2024-06-20')
        ->set('toDate', '2024-06-20')
        ->set('fromTime', '10:00')
        ->set('toTime', '14:00')
        ->call('saveShift')
        ->assertDispatched('toast', type: 'error');

    // Should not create the shift
    expect(Shift::where('is_unavailable', false)->count())->toBe(0);
});

// Drag and drop tests
it('can move shift to new date', function () {
    $assistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'ends_at' => Carbon::parse('2024-06-20 12:00'),
        'is_unavailable' => false,
    ]);

    Livewire::test(Calendar::class)
        ->call('moveShift', $shift->id, '2024-06-25')
        ->assertDispatched('toast', type: 'success');

    $shift->refresh();
    expect($shift->starts_at->format('Y-m-d'))->toBe('2024-06-25');
});

it('can move shift to new date with specific time', function () {
    $assistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'ends_at' => Carbon::parse('2024-06-20 12:00'),
    ]);

    Livewire::test(Calendar::class)
        ->call('moveShift', $shift->id, '2024-06-25', '10:00')
        ->assertDispatched('toast', type: 'success');

    $shift->refresh();
    expect($shift->starts_at->format('Y-m-d H:i'))->toBe('2024-06-25 10:00');
});

it('preserves shift duration when moving', function () {
    $assistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'ends_at' => Carbon::parse('2024-06-20 12:00'), // 4 hours
    ]);

    Livewire::test(Calendar::class)
        ->call('moveShift', $shift->id, '2024-06-25', '14:00');

    $shift->refresh();
    expect($shift->starts_at->format('H:i'))->toBe('14:00');
    expect($shift->ends_at->format('H:i'))->toBe('18:00'); // Still 4 hours
});

it('can create shift from drag', function () {
    $assistant = Assistant::factory()->create();

    // createShiftFromDrag now opens modal with pre-filled data instead of creating directly
    Livewire::test(Calendar::class)
        ->call('createShiftFromDrag', $assistant->id, '2024-06-20', '10:00')
        ->assertSet('showModal', true)
        ->assertSet('assistantId', $assistant->id)
        ->assertSet('fromDate', '2024-06-20')
        ->assertSet('fromTime', '10:00');
});

it('can resize shift', function () {
    $assistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'ends_at' => Carbon::parse('2024-06-20 10:00'),
        'is_all_day' => false,
    ]);

    Livewire::test(Calendar::class)
        ->call('resizeShift', $shift->id, 240) // 4 hours
        ->assertDispatched('toast', type: 'success');

    $shift->refresh();
    expect($shift->ends_at->format('H:i'))->toBe('12:00');
});

it('enforces minimum duration on resize', function () {
    $assistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'ends_at' => Carbon::parse('2024-06-20 10:00'),
        'is_all_day' => false,
    ]);

    Livewire::test(Calendar::class)
        ->call('resizeShift', $shift->id, 5); // Only 5 minutes - should be bumped to 15

    $shift->refresh();
    expect($shift->ends_at->format('H:i'))->toBe('08:15');
});

// Quick create tests
it('can open quick create popup', function () {
    Livewire::test(Calendar::class)
        ->assertSet('showQuickCreate', false)
        ->call('openQuickCreate', '2024-06-20', '10:00')
        ->assertSet('showQuickCreate', true)
        ->assertSet('quickCreateDate', '2024-06-20')
        ->assertSet('quickCreateTime', '10:00');
});

it('can close quick create popup', function () {
    Livewire::test(Calendar::class)
        ->call('openQuickCreate', '2024-06-20', '10:00')
        ->call('closeQuickCreate')
        ->assertSet('showQuickCreate', false);
});

it('can quick create shift', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Calendar::class)
        ->call('openQuickCreate', '2024-06-20', '10:00')
        ->call('quickCreateShift', $assistant->id)
        ->assertSet('showQuickCreate', false)
        ->assertDispatched('toast', type: 'success');

    $this->assertDatabaseHas('shifts', [
        'assistant_id' => $assistant->id,
    ]);
});

// Duplicate tests
it('can duplicate shift to another date', function () {
    $assistant = Assistant::factory()->create();
    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'ends_at' => Carbon::parse('2024-06-20 12:00'),
        'note' => 'Original note',
    ]);

    Livewire::test(Calendar::class)
        ->call('duplicateShift', $shift->id, '2024-06-25')
        ->assertDispatched('toast', type: 'success');

    expect(Shift::count())->toBe(2);

    $duplicated = Shift::where('id', '!=', $shift->id)->first();
    expect($duplicated->starts_at->format('Y-m-d'))->toBe('2024-06-25');
    expect($duplicated->note)->toBe('Original note');
});

// Recurring shift tests
it('can create recurring unavailable entries', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Calendar::class)
        ->call('openModal', '2024-06-20')
        ->set('assistantId', $assistant->id)
        ->set('isUnavailable', true)
        ->set('isRecurring', true)
        ->set('recurringInterval', 'weekly')
        ->set('recurringEndType', 'count')
        ->set('recurringCount', 4)
        ->set('fromDate', '2024-06-20')
        ->set('toDate', '2024-06-20')
        ->call('saveShift')
        ->assertHasNoErrors();

    // Should create 4 entries
    expect(Shift::where('is_unavailable', true)->count())->toBe(4);
});

it('shows recurring dialog when deleting recurring shift', function () {
    $assistant = Assistant::factory()->create();
    $groupId = (string) \Illuminate\Support\Str::uuid();

    $shift = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'is_unavailable' => true,
        'recurring_group_id' => $groupId,
    ]);

    Livewire::test(Calendar::class)
        ->call('editShift', $shift->id)
        ->call('deleteShift')
        ->assertSet('showRecurringDialog', true)
        ->assertSet('recurringAction', 'delete');
});

it('can delete single shift from recurring group', function () {
    $assistant = Assistant::factory()->create();
    $groupId = (string) \Illuminate\Support\Str::uuid();

    // Create 3 recurring shifts
    $shift1 = Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-20 08:00'),
        'is_unavailable' => true,
        'recurring_group_id' => $groupId,
    ]);
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-06-27 08:00'),
        'is_unavailable' => true,
        'recurring_group_id' => $groupId,
    ]);
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2024-07-04 08:00'),
        'is_unavailable' => true,
        'recurring_group_id' => $groupId,
    ]);

    Livewire::test(Calendar::class)
        ->call('editShift', $shift1->id)
        ->call('confirmDeleteShift', 'single');

    expect(Shift::count())->toBe(2);
});

// Helper property tests
it('returns correct Norwegian month name', function () {
    Livewire::test(Calendar::class)
        ->assertSet('year', 2024)
        ->assertSet('month', 6);

    $component = Livewire::test(Calendar::class);
    expect($component->get('currentMonthName'))->toBe('Juni');
});

it('returns assistants sorted by name', function () {
    Assistant::factory()->create(['name' => 'Charlie']);
    Assistant::factory()->create(['name' => 'Alice']);
    Assistant::factory()->create(['name' => 'Bob']);

    $component = Livewire::test(Calendar::class);
    $assistants = $component->get('assistants');

    expect($assistants[0]->name)->toBe('Alice');
    expect($assistants[1]->name)->toBe('Bob');
    expect($assistants[2]->name)->toBe('Charlie');
});

it('can save shift and create another', function () {
    $assistant = Assistant::factory()->create();

    Livewire::test(Calendar::class)
        ->call('openModal', '2024-06-20', '08:00')
        ->set('assistantId', $assistant->id)
        ->set('fromDate', '2024-06-20')
        ->set('toDate', '2024-06-20')
        ->call('saveShift', true) // createAnother = true
        ->assertHasNoErrors()
        ->assertSet('showModal', true) // Modal stays open
        ->assertSet('editingShiftId', null); // Form is reset
});

// Direct navigation tests
it('can go to specific month', function () {
    Livewire::test(Calendar::class)
        ->assertSet('month', 6)
        ->call('goToMonth', 3)
        ->assertSet('month', 3)
        ->assertSet('year', 2024); // Year should not change
});

it('can go to specific year', function () {
    Livewire::test(Calendar::class)
        ->assertSet('year', 2024)
        ->call('goToYear', 2023)
        ->assertSet('year', 2023)
        ->assertSet('month', 6); // Month should not change
});

it('returns available years from shift data', function () {
    // Create shifts in different years
    $assistant = Assistant::factory()->create();
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2022-05-15 08:00'),
        'ends_at' => Carbon::parse('2022-05-15 12:00'),
    ]);
    Shift::factory()->create([
        'assistant_id' => $assistant->id,
        'starts_at' => Carbon::parse('2023-08-20 08:00'),
        'ends_at' => Carbon::parse('2023-08-20 12:00'),
    ]);

    $component = Livewire::test(Calendar::class);
    $years = $component->get('availableYears');

    expect($years)->toContain(2022)
        ->and($years)->toContain(2023)
        ->and($years)->toContain(2024); // Current year always included
});
