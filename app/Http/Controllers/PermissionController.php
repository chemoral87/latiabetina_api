<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\Profile;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller {
  public function index(Request $request) {
    $query = Permission::query();
    $itemsPerPage = $request->itemsPerPage;
    $sortBy = $request->get('sortBy');
    $sortDesc = $request->get('sortDesc');
    $filter = $request->get("filter");

    foreach ($request->get('sortBy') as $index => $column) {
      $sortDirection = ($sortDesc[$index] == 'true') ? 'DESC' : 'ASC';
      $query = $query->orderBy($column, $sortDirection);
    }
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }

    $permissions = $query->paginate($itemsPerPage);
    return new DataSetResource($permissions);
  }

  public function filter(Request $request) {
    $filter = $request->queryText;
    $ids = isset($request->ids) ? $request->ids : [];
    $permissions = Permission::select("name", "id")
      ->whereNotIn("id", $ids)
      ->where("name", "like", "%" . $filter . "%")
      ->orderBy("name")->paginate(7);
    return $permissions->items();
  }

  public function create(Request $request) {
    $this->validate($request, [
      'name' => 'required|unique:permissions,name',

    ]);

    $role = Permission::create(['name' => $request->input('name')]);

    return [
      'success' => __('messa.permission_create'),
    ];
  }

  public function update(Request $request, $id) {
    $this->validate($request, [
      'name' => 'required',

    ]);

    $role = Permission::find($id);
    $role->name = $request->input('name');
    $role->save();

    return [
      'success' => __('messa.permission_update'),
    ];
  }

  public function distribution(Request $request, $id) {
    $permission = Permission::findOrFail($id);

    // Find profiles whose roles have this permission (eager-load both org and roles)
    $profiles = Profile::whereHas('roles.permissions', function ($query) use ($permission) {
      $query->where('permissions.id', $permission->id);
    })->with([
      'organization:id,name,short_code',
      'roles' => fn($q) => $q->whereHas('permissions', fn($q2) => $q2->where('permissions.id', $permission->id)),
    ])->orderBy('user_id')->get();

    // Build unique role+org combinations
    $rolesMap = [];
    foreach ($profiles as $profile) {
      foreach ($profile->roles as $role) {
        $key = $role->id . '-' . $profile->org_id;
        if (!isset($rolesMap[$key])) {
          $rolesMap[$key] = [
            'id' => $role->id,
            'role_name' => $role->name,
            'org_id' => $profile->org_id,
            'organization_name' => $profile->organization->name,
            'organization_short_code' => $profile->organization->short_code,
          ];
        }
      }
    }

    // Filter by organization if org_id is provided
    $roles = array_values($rolesMap);
    if ($request->has('org_id') && $request->org_id) {
      $roles = array_values(array_filter($roles, fn($r) => $r['org_id'] == $request->org_id));
    }

    return response()->json([
      'permission' => ['id' => $permission->id, 'name' => $permission->name],
      'roles' => $roles,
    ]);
  }

  public function delete($id) {
    Permission::find($id)->delete();
    return ['success' => __('messa.permission_delete')];
  }

}
