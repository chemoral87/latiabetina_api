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
   * Get the data to broadcast. Seat ids are grouped by section letter to keep
   * the payload small: { "A": ["1-6", "1-7"], "B": ["2-1"] }.
   */
  public function broadcastWith(): array {
    $grouped = [];
    foreach ((array) $this->seatIds as $seatId) {
      $dash = strpos($seatId, '-');
      if ($dash === false) {
        $grouped['?'][] = $seatId;
        continue;
      }
      $letter = substr($seatId, 0, $dash);
      $grouped[$letter][] = substr($seatId, $dash + 1);
    }
    ksort($grouped);
    return [
      'z' => $grouped,
      's' => $this->status,
      // 'i' => $this->auditoriumEventId,
      't' => $this->timestamp,
    ];
  }
}
