<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;

final class Profile extends Model {
  protected $guard_name = 'web';
  use HasFactory, HasRoles;

  protected $fillable = ['user_id', 'org_id', 'favorite'];
// append organization name to profile
  protected $appends = ['organization_name', 'organization_short_code'];

  // not append organization
  protected $hidden = ['organization'];

  public function user(): BelongsTo {
    return $this->belongsTo(User::class);
  }

  public function organization(): BelongsTo {
    return $this->belongsTo(Organization::class, 'org_id');
  }

  public function getOrganizationNameAttribute(): string {
    return $this->organization->name;
  }

  public function getOrganizationShortCodeAttribute(): string {
    return $this->organization->short_code;
  }

}
