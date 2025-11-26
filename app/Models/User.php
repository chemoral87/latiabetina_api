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

  public function getOrgsByPermission($permission) {
    $orgIds = [];
    $orgs = \App\Models\Organization::all();
    foreach ($orgs as $org) {
      if ($this->hasPermissionTo($permission, null, $org->id)) {
        $orgIds[] = $org->id;
      }
    }
    return $orgIds;
  }

  // No Auditing of password

  public function getAuditIgnore() {
    return ['password'];
  }
}
