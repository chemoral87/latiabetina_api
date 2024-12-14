<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model {
  use HasFactory;
  protected $fillable = [
    'org_id',
    'name',
    'address',
    'city',
    'state',
    'zip',
    'country',
    'phone',
    'latitude',
    'longitude',
    'created_by',
    'updated_by',
  ];
}
