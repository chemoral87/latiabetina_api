<?php

namespace App\Events;

use App\Models\Sale;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Sale $sale;

    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    /**
     * Broadcast on the per-org sales channel so sales index pages can update.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('pos.sales.' . $this->sale->org_id),
        ];
    }

    /**
     * Event name as received by Laravel Echo: ".sale.completed"
     */
    public function broadcastAs(): string
    {
        return 'sale.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'id'       => $this->sale->id,
            'number'   => $this->sale->number,
            'status'   => $this->sale->status,
            'org_id'   => $this->sale->org_id,
        ];
    }
}
