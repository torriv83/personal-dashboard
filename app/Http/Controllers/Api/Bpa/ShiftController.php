<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Bpa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Bpa\MoveShiftRequest;
use App\Http\Requests\Api\Bpa\ResizeShiftRequest;
use App\Http\Requests\Api\Bpa\StoreShiftRequest;
use App\Http\Requests\Api\Bpa\UpdateShiftRequest;
use App\Http\Resources\Bpa\ShiftResource;
use App\Models\Shift;
use App\Services\BpaShiftService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(
        private readonly BpaShiftService $shiftService,
    ) {}

    /**
     * Create a new shift (single or recurring).
     *
     * POST /api/bpa/shifts
     */
    public function store(StoreShiftRequest $request): JsonResponse
    {
        $data = $request->validated();
        $isAllDay = (bool) ($data['is_all_day'] ?? false);
        $isUnavailable = (bool) ($data['is_unavailable'] ?? false);
        $isRecurring = (bool) ($data['is_recurring'] ?? false);

        $startsAt = $isAllDay
            ? Carbon::parse($data['from_date'])->startOfDay()
            : Carbon::parse($data['from_date'] . ' ' . $data['from_time']);

        $endsAt = $isAllDay
            ? Carbon::parse($data['to_date'])->endOfDay()
            : Carbon::parse($data['to_date'] . ' ' . $data['to_time']);

        // Handle recurring unavailability
        if ($isUnavailable && $isRecurring) {
            $baseData = [
                'assistant_id' => $data['assistant_id'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'is_unavailable' => true,
                'is_all_day' => $isAllDay,
                'note' => $data['note'] ?? null,
            ];

            $result = $this->shiftService->createRecurringShifts(
                baseData: $baseData,
                recurringInterval: $data['recurring_interval'] ?? 'weekly',
                recurringEndType: $data['recurring_end_type'] ?? 'count',
                recurringCount: (int) ($data['recurring_count'] ?? 4),
                recurringEndDate: $data['recurring_end_date'] ?? null,
                isAllDay: $isAllDay,
            );

            $this->invalidateCacheForDate($startsAt);

            $count = $result['count'];

            return response()->json([
                'shifts' => ShiftResource::collection($result['shifts']),
                'message' => "{$count} utilgjengelig-oppføringer ble opprettet",
            ], 201);
        }

        // Single shift creation
        $result = $this->shiftService->createShift(
            assistantId: (int) $data['assistant_id'],
            startsAt: $startsAt,
            endsAt: $endsAt,
            isUnavailable: $isUnavailable,
            isAllDay: $isAllDay,
            note: $data['note'] ?? null,
        );

        if ($result['error']) {
            return response()->json(['message' => $result['error']], 422);
        }

        $this->invalidateCacheForDate($startsAt);

        return response()->json([
            'shift' => new ShiftResource($result['shift']->load('assistant')),
            'message' => 'Vakt opprettet',
        ], 201);
    }

    /**
     * Update an existing shift with optional recurring scope.
     *
     * PUT /api/bpa/shifts/{shift}
     */
    public function update(UpdateShiftRequest $request, Shift $shift): JsonResponse
    {
        $data = $request->validated();
        $scope = $data['scope'] ?? 'single';
        $isAllDay = (bool) ($data['is_all_day'] ?? $shift->is_all_day);

        $startsAt = $isAllDay
            ? Carbon::parse($data['from_date'])->startOfDay()
            : Carbon::parse($data['from_date'] . ' ' . $data['from_time']);

        $endsAt = $isAllDay
            ? Carbon::parse($data['to_date'])->endOfDay()
            : Carbon::parse($data['to_date'] . ' ' . $data['to_time']);

        $updateData = [
            'assistant_id' => $data['assistant_id'],
            'is_unavailable' => $data['is_unavailable'] ?? $shift->is_unavailable,
            'is_all_day' => $isAllDay,
            'note' => $data['note'] ?? null,
        ];

        $result = $this->shiftService->updateShift($shift, $updateData, $startsAt, $endsAt, $isAllDay, $scope);

        $this->invalidateCacheForDate($startsAt);

        $shift->refresh()->load('assistant');

        $count = $result['count'];
        $message = $count > 1 ? "{$count} oppføringer ble oppdatert" : 'Vakt oppdatert';

        return response()->json([
            'shift' => new ShiftResource($shift),
            'message' => $message,
        ]);
    }

    /**
     * Delete or archive a shift with optional recurring scope.
     *
     * DELETE /api/bpa/shifts/{shift}
     */
    public function destroy(Request $request, Shift $shift): JsonResponse
    {
        $request->validate([
            'scope' => ['sometimes', 'in:single,future,all'],
            'type' => ['sometimes', 'in:delete,archive'],
        ]);

        $scope = $request->input('scope', 'single');
        $type = $request->input('type', 'delete');
        $forceDelete = $type === 'delete';

        $this->invalidateCacheForDate($shift->starts_at);

        $result = $this->shiftService->deleteShift($shift, $scope, $forceDelete);

        $count = $result['count'];
        $verb = $forceDelete ? 'slettet' : 'arkivert';
        $message = $count > 1 ? "{$count} oppføringer ble {$verb}" : "Vakt {$verb}";

        return response()->json(['message' => $message]);
    }

    /**
     * Move a shift to a new date/time with optional recurring scope.
     *
     * POST /api/bpa/shifts/{shift}/move
     */
    public function move(MoveShiftRequest $request, Shift $shift): JsonResponse
    {
        $data = $request->validated();
        $scope = $data['scope'] ?? 'single';
        $newDate = $data['new_date'];
        $newTime = $data['new_time'] ?? null;

        $this->invalidateCacheForDate($shift->starts_at);

        $result = $this->shiftService->moveShift($shift, $newDate, $newTime, $scope);

        if ($result['error']) {
            return response()->json(['message' => $result['error']], 422);
        }

        $this->invalidateCacheForDate(Carbon::parse($newDate));

        $count = $result['count'];
        $message = $count > 1 ? "{$count} oppføringer ble flyttet" : 'Vakt flyttet';

        return response()->json([
            'shift' => $result['shift'] ? new ShiftResource($result['shift']->load('assistant')) : null,
            'message' => $message,
        ]);
    }

    /**
     * Resize a shift by changing its duration.
     *
     * POST /api/bpa/shifts/{shift}/resize
     */
    public function resize(ResizeShiftRequest $request, Shift $shift): JsonResponse
    {
        $durationMinutes = (int) $request->validated()['duration_minutes'];

        $result = $this->shiftService->resizeShift($shift, $durationMinutes);

        if ($result['error']) {
            return response()->json(['message' => $result['error']], 422);
        }

        $this->invalidateCacheForDate($shift->starts_at);

        return response()->json([
            'shift' => new ShiftResource($result['shift']->load('assistant')),
            'message' => 'Varighet endret',
        ]);
    }

    /**
     * Duplicate a shift to a target date.
     *
     * POST /api/bpa/shifts/{shift}/duplicate
     */
    public function duplicate(Request $request, Shift $shift): JsonResponse
    {
        $request->validate([
            'target_date' => ['sometimes', 'nullable', 'date'],
        ]);

        $targetDate = $request->input('target_date');
        $newShift = $this->shiftService->duplicateShift($shift, $targetDate);

        $targetCarbon = Carbon::parse($targetDate ?? $shift->starts_at->format('Y-m-d'));
        $this->invalidateCacheForDate($targetCarbon);

        return response()->json([
            'shift' => new ShiftResource($newShift->load('assistant')),
            'message' => 'Vakt duplisert',
        ], 201);
    }

    /**
     * Quickly create a shift from a date/time click.
     *
     * POST /api/bpa/shifts/quick-create
     */
    public function quickCreate(Request $request): JsonResponse
    {
        $request->validate([
            'assistant_id' => ['required', 'integer', 'exists:assistants,id'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'nullable', 'date_format:H:i'],
        ], [
            'assistant_id.required' => 'Velg en assistent',
            'assistant_id.exists' => 'Ugyldig assistent',
            'date.required' => 'Dato er påkrevd',
            'time.required' => 'Starttidspunkt er påkrevd',
        ]);

        $assistantId = (int) $request->input('assistant_id');
        $date = $request->input('date');
        $time = $request->input('time');
        $endTime = $request->input('end_time');

        $startsAt = Carbon::parse($date . ' ' . $time);
        $endsAt = $endTime
            ? Carbon::parse($date . ' ' . $endTime)
            : $startsAt->copy()->addHours(3);

        $result = $this->shiftService->createShift(
            assistantId: $assistantId,
            startsAt: $startsAt,
            endsAt: $endsAt,
            isUnavailable: false,
            isAllDay: false,
        );

        if ($result['error']) {
            return response()->json(['message' => $result['error']], 422);
        }

        $this->invalidateCacheForDate($startsAt);

        return response()->json([
            'shift' => new ShiftResource($result['shift']->load('assistant')),
            'message' => 'Vakt opprettet',
        ], 201);
    }

    /**
     * Invalidate calendar caches for the given date.
     */
    private function invalidateCacheForDate(Carbon $date): void
    {
        $this->shiftService->invalidateCalendarCache($date->year, $date->month, $date->day);
    }
}
