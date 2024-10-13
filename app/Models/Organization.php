<?php

namespace App\Models;

use App\Models\OrganizationConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model {
  use HasFactory;
  protected $fillable = [
    'name',
    'short_code',
    'description',
  ];

  public function config() {
    return $this->hasMany(OrganizationConfig::class, 'org_id', 'id');
  }
}
