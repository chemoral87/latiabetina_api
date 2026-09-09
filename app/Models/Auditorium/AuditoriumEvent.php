<?php

declare(strict_types=1);

namespace App\Models\Auditorium;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriumEvent extends Model {
  protected $fillable = [
    'event_date',
    'time',
    'config',
    'auditorium_id',
    'org_id',
  ];

  public function auditorium(): BelongsTo {
    return $this->belongsTo(Auditorium::class);
  }
}