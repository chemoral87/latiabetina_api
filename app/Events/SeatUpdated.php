<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeatUpdated implements ShouldBroadcast {
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $seatIds;
  public $status;
  public $auditoriumEventId;
  public $timestamp;

  /**
   * Create a new event instance.
   */
  public function __construct($seatIds, $status, $auditoriumEventId, $timestamp) {
    $this->seatIds = $seatIds;
    $this->status = $status;
    $this->auditoriumEventId = $auditoriumEventId;
    $this->timestamp = $timestamp;
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return array<int, \Illuminate\Broadcasting\Channel>
   */
  public function broadcastOn(): array {
    return [
      new Channel('auditorium-event.' . $this->auditoriumEventId),
    ];
  }

  /**
   * The event's broadcast name.
   */
  public function broadcastAs(): string {
    return 'seat.updated';
  }

  /**
   * Get the data to broadcast.
   */
  public function broadcastWith(): array {
    return [
      'z' => $this->seatIds,
      's' => $this->status,
      // 'i' => $this->auditoriumEventId,
      't' => $this->timestamp,
    ];
  }
}
