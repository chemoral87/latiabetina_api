<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriumEventSeat extends Model {
  protected $fillable = [
    'auditorium_event_id',
    'seat_id',
    'status',
    'created_by',
  ];

  public function auditoriumEvent() {
    return $this->belongsTo(AuditoriumEvent::class);
  }

  public function creator() {
    return $this->belongsTo(User::class, 'created_by');
  }
}
