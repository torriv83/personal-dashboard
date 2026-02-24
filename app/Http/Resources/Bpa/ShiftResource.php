<?php

declare(strict_types=1);

namespace App\Http\Resources\Bpa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Shift
 */
class ShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assistant_id' => $this->assistant_id,
            'assistant_name' => $this->assistant_name,
            'assistant_color' => $this->assistant?->color,
            'assistant_initials' => $this->assistant?->initials,
            'assistant_short_name' => $this->assistant?->short_name,
            'starts_at' => $this->starts_at->format('Y-m-d\TH:i:s'),
            'ends_at' => $this->ends_at->format('Y-m-d\TH:i:s'),
            'date' => $this->starts_at->format('Y-m-d'),
            'start_time' => $this->starts_at->format('H:i'),
            'end_time' => $this->ends_at->format('H:i'),
            'duration_minutes' => $this->duration_minutes,
            'formatted_duration' => $this->formatted_duration,
            'time_range' => $this->time_range,
            'compact_time_range' => $this->compact_time_range,
            'is_unavailable' => $this->is_unavailable,
            'is_all_day' => $this->is_all_day,
            'is_recurring' => $this->isRecurring(),
            'recurring_group_id' => $this->recurring_group_id,
            'note' => $this->note,
        ];
    }
}
