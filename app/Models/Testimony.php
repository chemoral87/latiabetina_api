<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Testimony extends Model {
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
