<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, AuditableContract {
  use HasApiTokens, HasFactory, Notifiable, HasRoles;
  use Auditable;
  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'last_name',
    'second_last_name',
    'email',
    'password',
    'email_verified_at',
    'cellphone',
    'birthday',
    'google_id',
    'avatar',
  ];

  public function getJWTIdentifier() {
    return $this->getKey();
  }

  public function getJWTCustomClaims() {
    return [];
  }

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
  ];

  public function profiles() {
    return $this->hasMany(Profile::class);
  }

  /**
   * Returns an array of org_ids for a given permission, or a map of all permissions to org_ids if no permission is given.
   *
   * @param string|null $permission
   * @return array
   */
  public function getOrgsByPermission($permission = null) {
    $permissions_orgs = [];
    foreach ($this->profiles as $profile) {
      foreach ($profile->roles as $role) {
        foreach ($role->permissions as $perm) {
          $permissions_orgs[$perm->name][$profile->org_id] = true;
        }
      }
      foreach ($profile->permissions as $perm) {
        $permissions_orgs[$perm->name][$profile->org_id] = true;
      }
    }
    // Convert to array of org_ids
    foreach ($permissions_orgs as &$orgIds) {
      $orgIds = array_keys($orgIds);
    }
    unset($orgIds);
    if ($permission) {
      return $permissions_orgs[$permission] ?? [];
    }
    return $permissions_orgs;
  }

  // No Auditing of password

  public function getAuditIgnore() {
    return ['password'];
  }
}
