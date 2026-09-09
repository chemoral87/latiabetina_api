<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ChurchMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'conso_sheet_id' => $this->conso_sheet_id,
            'name' => $this->name,
            'last_name' => $this->last_name,
            'second_last_name' => $this->second_last_name,
            'years_old' => $this->years_old,
            'number_of_children' => $this->number_of_children,
            'cellphone' => $this->cellphone,
            'address' => $this->address,
            'marriage_status' => $this->marriage_status,
            'url_image_s3' => $this->url_image_s3,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'creator' => new UserBasicResource($this->whenLoaded('creator')),
            'medals' => ChurchMemberMedalResource::collection($this->whenLoaded('medals')),
            'consolidators' => UserBasicResource::collection($this->whenLoaded('consolidators')),
        ];
    }
}
