<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditoriumEvent extends Model {
  protected $fillable = [
    'event_date',
    'config',
    'auditorium_id',
    'org_id',
  ];

  public function auditorium() {
    return $this->belongsTo(Auditorium::class);
  }
}
