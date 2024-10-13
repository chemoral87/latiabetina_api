<?php

namespace App\Models;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Profile extends Model {
  use HasFactory, HasRoles;

  protected $fillable = ['user_id', 'org_id', 'favorite'];
// append organization name to profile
  protected $appends = ['organization_name', 'organization_short_code'];

  // not append organization
  protected $hidden = ['organization'];

  public function user() {
    return $this->belongsTo(User::class);
  }

  public function organization() {
    return $this->belongsTo(Organization::class, 'org_id');
  }

  public function getOrganizationNameAttribute() {
    return $this->organization->name;
  }

  public function getOrganizationShortCodeAttribute() {
    return $this->organization->short_code;
  }

}
