<?php

declare(strict_types=1);

namespace App\Http\Resources\Bpa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Assistant
 */
class AssistantResource extends JsonResource
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
            'name' => $this->name,
            'color' => $this->color,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'initials' => $this->initials,
            'short_name' => $this->short_name,
            'employee_number' => $this->employee_number,
            'formatted_number' => $this->formatted_number,
            'token' => $this->token,
        ];
    }
}
