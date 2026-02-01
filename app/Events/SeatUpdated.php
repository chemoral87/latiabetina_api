<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeatUpdated implements ShouldBroadcast {
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $seats;
  public $auditoriumEventId;
  public $timestamp;

  /**
   * Create a new event instance.
   */
  public function __construct($seats, $auditoriumEventId, $timestamp) {
    $this->seats = $seats;
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
      'seats' => $this->seats,
      'auditorium_event_id' => $this->auditoriumEventId,
      'timestamp' => $this->timestamp,
    ];
  }
}
