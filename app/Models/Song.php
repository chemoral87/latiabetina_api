<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Song extends Model {
  use HasFactory;

  protected $fillable = [
    'title',
    'artist',
    'key',
    'tempo',
    'content',
    'org_id',
    'created_by',
  ];

  protected $casts = [
    'content' => 'array',
  ];

  public function organization(): BelongsTo {
    return $this->belongsTo(Organization::class, 'org_id');
  }

  public function creator(): BelongsTo {
    return $this->belongsTo(User::class, 'created_by');
  }
}