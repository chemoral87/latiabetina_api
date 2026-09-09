<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrganizationConfig extends Model {
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

  public function getKeyAttribute(): string {
    return $this->config->key;
  }

  public function config(): BelongsTo {
    return $this->belongsTo(Config::class, 'config_id', 'id');
  }

  public function organization(): BelongsTo {
    return $this->belongsTo(Organization::class, 'org_id', 'id');
  }
}
