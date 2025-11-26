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
    $profilesRolesPermissions = [];

    // Loop through each profile
    foreach ($this->profiles as $profile) {
      // Get organization short code
      $orgCode = $profile->organization->short_code;

      $orgs[] = [
        'id' => $profile->org_id,
        'name' => $profile->organization->name,
        'short_code' => $orgCode,
      ];

      // Loop through roles associated with the profile
      foreach ($profile->roles as $role) {
        // Loop through permissions associated with the role
        foreach ($role->permissions as $permission) {
          $profilesRolesPermissions[] = [
            'org_id' => $profile->org_id,
            'org_code' => $orgCode,
            'role_name' => $role->name,
            'permission_name' => $permission->name,
          ];

          // Store permission for the organization
          $permissions_orgs[$permission->name][$profile->org_id] = true;

        }
      }

      // Loop through direct permissions of the profile
      foreach ($profile->permissions as $permission) {
        $profilesRolesPermissions[] = [
          'org_id' => $profile->org_id,
          'org_code' => $orgCode,
          'role_name' => null,
          'permission_name' => $permission->name,
        ];

        // Store permission for the organization
        $permissions_orgs[$permission->name][$profile->org_id] = true;
      }
    }

    // Extract unique organization IDs for each permission
    foreach ($permissions_orgs as &$orgIds) {
      $orgIds = array_keys($orgIds);
    }


    return [
      'id' => $this->id,
      'name' => $this->name,
      'last_name' => $this->last_name,
      'second_last_name' => $this->second_last_name,
      'email' => $this->email,
      'email_verified' => isset($this->email_verified_at) ? 1 : 0,
      'permissions_org' => $permissions_orgs,
      'orgs' => $orgs ?? [],
    ];

  }
}
