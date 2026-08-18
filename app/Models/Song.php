<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Song extends Model {
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

  public function organization() {
    return $this->belongsTo(Organization::class, 'org_id');
  }

  public function creator() {
    return $this->belongsTo(User::class, 'created_by');
  }
}