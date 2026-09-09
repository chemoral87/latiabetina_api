<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'org_id' => $this->org_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => $this->price,
            'stock' => $this->stock,
            'order' => $this->order,
            'hidden' => $this->hidden,
            'requires_preparation' => $this->requires_preparation,
            'image_s3' => $this->image_s3,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
