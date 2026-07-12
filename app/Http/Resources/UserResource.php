<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
  /**
   * Transform the resource into an array.
   *
   * @return array<string, mixed>
   */
  public function toArray($request) {

    $permissions_orgs = [];
    $orgs = [];
    $allPermissions = [];
    $roles_orgs = [];

    // Gather all permissions and roles from all profiles
    foreach ($this->profiles as $profile) {
      $orgCode = $profile->organization->short_code;
      $orgs[] = [
        'id' => $profile->org_id,
        'name' => $profile->organization->name,
        'short_code' => $orgCode,
      ];
      foreach ($profile->roles as $role) {
        $roles_orgs[$role->name][$profile->org_id] = true;
        foreach ($role->permissions as $permission) {
          $allPermissions[$permission->name] = true;
        }
      }
      foreach ($profile->permissions as $permission) {
        $allPermissions[$permission->name] = true;
      }
    }
    // For each permission, use getOrgsByPermission to get orgs
    foreach (array_keys($allPermissions) as $permissionName) {
      $permissions_orgs[$permissionName] = $this->getOrgsByPermission($permissionName);
    }
    // Convert roles map to array of org_ids
    foreach ($roles_orgs as &$orgIds) {
      $orgIds = array_keys($orgIds);
    }
    unset($orgIds);

    return [
      'id' => $this->id,
      'name' => $this->name,
      'last_name' => $this->last_name,
      'second_last_name' => $this->second_last_name,
      'email' => $this->email,
      'email_verified' => isset($this->email_verified_at) ? 1 : 0,
      'permissions_org' => $permissions_orgs,
      'orgs' => $orgs,
      'roles_org' => $roles_orgs,
    ];

  }
}
