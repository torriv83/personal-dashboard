<?php

declare(strict_types=1);

namespace App\Http\Resources\Bpa;

use App\Services\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CalendarEvent
 */
class CalendarEventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var CalendarEvent $event */
        $event = $this->resource;

        return [
            'id' => $event->getUniqueKey(),
            'title' => $event->title,
            'description' => $event->description,
            'location' => $event->location,
            'starts_at' => $event->starts_at->format('Y-m-d\TH:i:s'),
            'ends_at' => $event->ends_at->format('Y-m-d\TH:i:s'),
            'date' => $event->starts_at->format('Y-m-d'),
            'start_time' => $event->starts_at->format('H:i'),
            'end_time' => $event->ends_at->format('H:i'),
            'is_all_day' => $event->is_all_day,
            'calendar_key' => $event->calendar_key,
            'calendar_label' => $event->calendar_label,
            'color' => $event->color,
        ];
    }
}
