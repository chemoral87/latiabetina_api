<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimony extends Model {
  use HasFactory;

  protected $fillable = [
    'name',
    'phone_number',
    'categories',
    'link',
    'description',
    'status_by',
    'status',
    'org_id',
  ];

  protected $casts = [
    'categories' => 'array',
    'status' => 'string',
    'status_by' => 'integer',
  ];
}
