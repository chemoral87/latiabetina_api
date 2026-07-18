<?php

namespace App\Events;

use App\Models\Sale;
use App\Models\SaleEventLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Sale $sale;

    public function __construct(Sale $sale)
    {
        $this->sale = $sale;

        // Log the event for audit/debug purposes (always, except in production)
        if (app()->environment() !== 'production') {
            $this->logEvent();
        }
    }

    /**
     * Whether this sale has at least one item that requires preparation.
     */
    public function hasPreparationItems(): bool
    {
        return $this->sale->items()
            ->whereHas('product', fn ($q) => $q->where('requires_preparation', true))
            ->exists();
    }

    /**
     * Log the event to the sale_event_logs table for audit purposes.
     */
    private function logEvent(): void
    {
        try {
            $sale = $this->sale->loadMissing('items.product', 'organization');

            $broadcastData = [
                'id'            => $sale->id,
                'number'        => $sale->number,
                'org_id'        => $sale->org_id,
                'customer_name' => $sale->customer_name,
                'sold_at'       => $sale->sold_at?->toIso8601String(),
                'organization'  => $sale->organization ? [
                    'id'   => $sale->organization->id,
                    'name' => $sale->organization->name,
                ] : null,
                'items'         => $sale->items->map(fn ($item) => [
                    'id'       => $item->id,
                    'quantity' => $item->quantity,
                    'product'  => [
                        'id'                  => $item->product->id,
                        'name'                => $item->product->name,
                        'description'         => $item->product->description,
                        'image_s3'            => $item->product->image_s3,
                        'requires_preparation'=> $item->product->requires_preparation,
                    ],
                ])->values()->toArray(),
            ];

            SaleEventLog::create([
                'sale_id' => $sale->id,
                'number' => $sale->number,
                'org_id' => $sale->org_id,
                'customer_name' => $sale->customer_name,
                'has_preparation_items' => $sale->items->contains(
                    fn ($item) => $item->product->requires_preparation === true
                ),
                'broadcast_data' => $broadcastData,
            ]);
        } catch (\Throwable $e) {
            // Don't let logging interrupt the main flow
            \Illuminate\Support\Facades\Log::warning('Failed to log SaleCreated event: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast on a public per-org channel so the KDS can filter by org.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('pos.kds.' . $this->sale->org_id),
        ];
    }

    /**
     * Event name as received by Laravel Echo: ".sale.created"
     */
    public function broadcastAs(): string
    {
        return 'sale.created';
    }

    /**
     * Only broadcast sales that contain at least one product requiring preparation.
     * This avoids unnecessary traffic for orders with no kitchen items.
     */
    public function broadcastWhen(): bool
    {
        return $this->hasPreparationItems();
    }

    public function broadcastWith(): array
    {
        // Load items with products and org so the KDS gets everything it needs in one push.
        $sale = $this->sale->loadMissing('items.product', 'organization');

        return [
            'id'            => $sale->id,
            'number'        => $sale->number,
            'org_id'        => $sale->org_id,
            'customer_name' => $sale->customer_name,
            'sold_at'       => $sale->sold_at?->toIso8601String(),
            'organization'  => $sale->organization ? [
                'id'   => $sale->organization->id,
                'name' => $sale->organization->name,
            ] : null,
            'items'         => $sale->items->map(fn ($item) => [
                'id'       => $item->id,
                'quantity' => $item->quantity,
                'product'  => [
                    'id'                  => $item->product->id,
                    'name'                => $item->product->name,
                    'description'         => $item->product->description,
                    'image_s3'            => $item->product->image_s3,
                    'requires_preparation'=> $item->product->requires_preparation,
                ],
            ])->values()->toArray(),
        ];
    }
}
