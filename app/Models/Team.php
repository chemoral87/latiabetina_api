<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Team extends Model {
  protected $table = 'teams';
  protected $fillable = [
    'name',
    'created_at',
    'updated_at',
  ];
}
