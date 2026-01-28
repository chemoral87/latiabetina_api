<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriumEventSeatLog extends Model {
  protected $table = 'auditorium_event_seats_log';

  protected $fillable = [
    'auditorium_event_id',
    'seat_ids',
    'status',
    'created_by',
  ];

  protected $casts = [
    'seat_ids' => 'array',
  ];

  public function auditoriumEvent() {
    return $this->belongsTo(AuditoriumEvent::class);
  }

  public function creator() {
    return $this->belongsTo(User::class, 'created_by');
  }
}
