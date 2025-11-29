<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auditorium extends Model {
  use HasFactory;
  protected $table = 'auditoriums';
  protected $fillable = [
    'name',
    'config',
    'org_id',
    'created_by',
  ];

}
