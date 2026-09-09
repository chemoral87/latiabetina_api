<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'concept_id' => $this->concept_id,
            'ticket_id' => $this->ticket_id,
            'unit' => $this->unit,
            'quantity' => $this->quantity,
            'amount' => $this->amount,
            'total' => $this->total,
            'date' => $this->date,
            'created_at' => $this->created_at?->toIso8601String(),
            'concept' => new ExpenseConceptResource($this->whenLoaded('concept')),
        ];
    }
}
