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
    'approved_by',
    'approved_date',
    'org_id',
  ];

  protected $casts = [
    'categories' => 'array',
    'approved_date' => 'datetime',
    'approved_by' => 'integer',
  ];
}
