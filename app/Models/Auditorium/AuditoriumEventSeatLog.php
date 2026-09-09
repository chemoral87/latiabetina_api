<?php

declare(strict_types=1);

namespace App\Models\Auditorium;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

  public function auditoriumEvent(): BelongsTo {
    return $this->belongsTo(AuditoriumEvent::class);
  }

  public function creator(): BelongsTo {
    return $this->belongsTo(User::class, 'created_by');
  }
}