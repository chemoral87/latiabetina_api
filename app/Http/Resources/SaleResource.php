<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'org_id' => $this->org_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'payment_method' => $this->payment_method,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'sold_at' => $this->sold_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'creator' => new UserBasicResource($this->whenLoaded('creator')),
        ];
    }
}
