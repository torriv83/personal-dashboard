<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskListResource extends JsonResource
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
            'slug' => $this->slug,
            'name' => $this->name,
            'is_shared' => $this->is_shared,
            'task_count' => $this->tasks->count(),
            'completed_count' => $this->tasks->where('status', 'completed')->count(),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
