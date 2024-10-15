<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Http\Resources\RoleShowResource;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller {
  // https://codingdriver.com/laravel-user-roles-and-permissions-tutorial-with-example.html
  public function index(Request $request) {
    $query = new Role;
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

    $roles = $query->with("permissions")->paginate($itemsPerPage);
    return new DataSetResource($roles);
  }

  public function show(Request $request, $id) {
    $userResource = new RoleShowResource(Role::find($id));
    return response()->json($userResource);
  }

  public function filter(Request $request) {
    $filter = $request->queryText;
    $ids = isset($request->ids) ? $request->ids : [];
    $roles = Role::select("name", "id")
      ->whereNotIn("id", $ids)
      ->where("name", "like", "%" . $filter . "%")
      ->orderBy("name")->paginate(7);
    return $roles->items();
  }

  public function create(Request $request) {
    $this->validate($request, [
      'name' => 'required|unique:roles,name',
      
    ]);

    $role = Role::create(['name' => $request->input('name')]);
    $role->syncPermissions($request->permissions);

    return ['success' => __('messa.role_create')];
  }

  public function update(Request $request, $id) {
    $this->validate($request, ['name' => 'required']);
    $role = Role::find($id);
    $role->name = $request->input('name');
    $role->save();
    // $role->syncPermissions($request->input('permissions'));
    return ['success' => __('messa.role_update')];
  }

  public function children(Request $request, $id) {
    $role = Role::find($id);
    if ($role) {
      $permissions_ids = $request->permissions_ids;
      $role->permissions()->sync($permissions_ids);
    }
    return ['success' => __('messa.role_permission_update')];
  }

  public function delete($id) {
    Role::find($id)->delete();
    return ['success' => __('messa.role_delete')];
  }

}
