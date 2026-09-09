<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\OrganizationConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Organization extends Model {
  use HasFactory;
  protected $fillable = [
    'name',
    'short_code',
    'description',
  ];

  public function config(): HasMany {
    return $this->hasMany(OrganizationConfig::class, 'org_id', 'id');
  }
}
