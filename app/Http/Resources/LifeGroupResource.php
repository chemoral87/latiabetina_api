<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LifeGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'reference' => $this->reference,
            'neighborhood' => $this->neighborhood,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'time' => $this->time,
            'day_of_week' => $this->day_of_week,
            'observations' => $this->observations,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'creator' => new UserBasicResource($this->whenLoaded('creator')),
            'people_count' => $this->whenCounted('people'),
            'sessions_count' => $this->whenCounted('sessions'),
        ];
    }
}
