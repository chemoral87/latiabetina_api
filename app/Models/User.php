<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

final class User extends Authenticatable implements JWTSubject, AuditableContract {
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
    'last_login_at',
    'cellphone',
    'birthday',
    'google_id',
    'avatar',
  ];

  public function getJWTIdentifier(): int {
    return $this->getKey();
  }

  public function getJWTCustomClaims(): array {
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
    'last_login_at' => 'datetime',
  ];

  public function profiles(): HasMany {
    return $this->hasMany(Profile::class);
  }

  public function lifeGroupsAsLeader(): BelongsToMany {
    return $this->belongsToMany(
      \App\Models\LifeGroup\LifeGroup::class,
      'life_group_leaders',
      'user_id',
      'life_group_id'
    )->withTimestamps();
  }

  /**
   * Returns an array of org_ids for a given permission, or a map of all permissions to org_ids if no permission is given.
   *
   * @param string|null $permission
   * @return array
   */
    public function getOrgsByPermission(?string $permission = null): array
    {
        $all = cache()->remember(
            "user:{$this->id}:org_permissions",
            now()->addMinutes(5),
            fn () => $this->computeOrgPermissions()
        );

        if ($permission) {
            return $all[$permission] ?? [];
        }

        return $all;
    }

    private function computeOrgPermissions(): array
    {
        $permissionsOrgs = [];
        foreach ($this->profiles as $profile) {
            foreach ($profile->roles as $role) {
                foreach ($role->permissions as $perm) {
                    $permissionsOrgs[$perm->name][$profile->org_id] = true;
                }
            }
            foreach ($profile->permissions as $perm) {
                $permissionsOrgs[$perm->name][$profile->org_id] = true;
            }
        }
        foreach ($permissionsOrgs as &$orgIds) {
            $orgIds = array_keys($orgIds);
        }
        unset($orgIds);

        return $permissionsOrgs;
    }

  // No Auditing of password

  public function getAuditIgnore(): array {
    return ['password'];
  }
}
