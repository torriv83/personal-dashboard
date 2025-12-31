<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Models\Shift;
use Carbon\Carbon;

class BpaQuotaService
{
    /**
     * Validate if a shift can be created within the remaining hours quota.
     *
     * @return array{valid: bool, error: string|null, remaining_minutes: int, shift_minutes: int}
     */
    public function validateShiftQuota(
        Carbon $startsAt,
        Carbon $endsAt,
        ?int $excludeShiftId = null
    ): array {
        $shiftDurationMinutes = (int) $startsAt->diffInMinutes($endsAt);

        if ($shiftDurationMinutes <= 0) {
            return [
                'valid' => true,
                'error' => null,
                'remaining_minutes' => 0,
                'shift_minutes' => 0,
            ];
        }

        $currentYear = Carbon::now()->year;
        $hoursPerWeek = Setting::getBpaHoursPerWeek();
        $yearlyQuotaMinutes = $hoursPerWeek * 52 * 60;

        $usedMinutes = Shift::query()
            ->worked()
            ->forYear($currentYear)
            ->when($excludeShiftId, fn ($q) => $q->where('id', '!=', $excludeShiftId))
            ->sum('duration_minutes');

        $remainingMinutes = (int) ($yearlyQuotaMinutes - $usedMinutes);

        if ($shiftDurationMinutes > $remainingMinutes) {
            return [
                'valid' => false,
                'error' => $this->formatQuotaError($shiftDurationMinutes, $remainingMinutes),
                'remaining_minutes' => $remainingMinutes,
                'shift_minutes' => $shiftDurationMinutes,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'remaining_minutes' => $remainingMinutes,
            'shift_minutes' => $shiftDurationMinutes,
        ];
    }

    /**
     * Format the quota exceeded error message.
     */
    private function formatQuotaError(int $shiftMinutes, int $remainingMinutes): string
    {
        $remainingFormatted = sprintf(
            '%d:%02d',
            intdiv(abs($remainingMinutes), 60),
            abs($remainingMinutes) % 60
        );

        $shiftFormatted = sprintf(
            '%d:%02d',
            intdiv($shiftMinutes, 60),
            $shiftMinutes % 60
        );

        return "Kan ikke registrere {$shiftFormatted} - kun {$remainingFormatted} timer igjen av vedtaket";
    }
}
