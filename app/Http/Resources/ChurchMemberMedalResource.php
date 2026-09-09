<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ChurchMemberMedalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'church_member_id' => $this->church_member_id,
            'medal' => $this->medal,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'creator' => new UserBasicResource($this->whenLoaded('creator')),
        ];
    }
}
