<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Professional extends Model {
  protected $fillable = [
    'user_id',
    'name',
    'last_name',
    'phone_number',
    'title',
    'description',
    'status',
  ];

  public function user(): BelongsTo {
    return $this->belongsTo(User::class);
  }
}
