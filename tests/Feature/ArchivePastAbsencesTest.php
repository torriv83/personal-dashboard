<?php

use App\Models\Shift;

it('archives past absence entries', function () {
    // Past absence - should be archived
    $pastAbsence = Shift::factory()->past()->unavailable()->create();

    // Future absence - should NOT be archived
    $futureAbsence = Shift::factory()->upcoming()->unavailable()->create();

    // Past regular shift - should NOT be archived
    $pastShift = Shift::factory()->past()->create();

    // Already archived absence - should remain archived (not double-counted)
    $alreadyArchived = Shift::factory()->past()->unavailable()->archived()->create();

    $this->artisan('shifts:archive-past-absences')->assertSuccessful();

    expect($pastAbsence->fresh()->is_archived)->toBeTrue()
        ->and($futureAbsence->fresh()->is_archived)->toBeFalse()
        ->and($pastShift->fresh()->is_archived)->toBeFalse()
        ->and($alreadyArchived->fresh()->is_archived)->toBeTrue();
});
