<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationConfig extends Model {
  use HasFactory;

  // append config name
  protected $appends = ['key'];

  protected $fillable = [
    'org_id',
    'config_id',
    'value',
  ];

  // Define hidden attributes
  protected $hidden = ['config'];

  public function getKeyAttribute() {
    return $this->config->key;
  }

  public function config() {
    return $this->belongsTo(Config::class, 'config_id', 'id');
  }

  public function organization() {
    return $this->belongsTo(Organization::class, 'org_id', 'id');
  }
}
