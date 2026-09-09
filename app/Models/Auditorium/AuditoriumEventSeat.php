<?php

declare(strict_types=1);

namespace App\Models\Auditorium;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriumEventSeat extends Model {
  protected $fillable = [
    'auditorium_event_id',
    'seat_id',
    'status',
    'created_by',
  ];

  public function auditoriumEvent(): BelongsTo {
    return $this->belongsTo(AuditoriumEvent::class);
  }

  public function creator(): BelongsTo {
    return $this->belongsTo(User::class, 'created_by');
  }
}