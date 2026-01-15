<?php

declare(strict_types=1);

use App\Models\Shift;

it('does not archive multi-day absence while still ongoing', function () {
    // Multi-day absence: started yesterday, ends tomorrow
    // Should NOT be archived because ends_at is in the future
    $ongoingAbsence = Shift::factory()->unavailable()->allDay()->create([
        'starts_at' => now()->subDay()->startOfDay(),
        'ends_at' => now()->addDay()->endOfDay(),
    ]);

    // Multi-day absence: started 5 days ago, ended yesterday
    // Should be archived because ends_at is in the past
    $endedAbsence = Shift::factory()->unavailable()->allDay()->create([
        'starts_at' => now()->subDays(5)->startOfDay(),
        'ends_at' => now()->subDay()->endOfDay(),
    ]);

    $this->artisan('shifts:archive-past-absences')->assertSuccessful();

    expect($ongoingAbsence->fresh()->trashed())->toBeFalse()
        ->and($endedAbsence->fresh()->trashed())->toBeTrue();
});

it('archives past absence entries by soft deleting them', function () {
    // Past absence - should be archived (soft deleted)
    $pastAbsence = Shift::factory()->past()->unavailable()->create();

    // Future absence - should NOT be archived
    $futureAbsence = Shift::factory()->upcoming()->unavailable()->create();

    // Past regular shift - should NOT be archived
    $pastShift = Shift::factory()->past()->create();

    // Already archived absence - should remain archived
    $alreadyArchived = Shift::factory()->past()->unavailable()->archived()->create();

    $this->artisan('shifts:archive-past-absences')->assertSuccessful();

    // Past absence should now be soft deleted (archived)
    // Note: fresh() uses newQueryWithoutScopes() so it returns the model even if soft-deleted
    expect($pastAbsence->fresh()->trashed())->toBeTrue()
        // Future absence should NOT be soft deleted
        ->and($futureAbsence->fresh()->trashed())->toBeFalse()
        // Past regular shift should NOT be soft deleted
        ->and($pastShift->fresh()->trashed())->toBeFalse()
        // Already archived should still be soft deleted
        ->and($alreadyArchived->fresh()->trashed())->toBeTrue();
});
