<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
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

  public function delete($id) {
    Permission::find($id)->delete();
    return ['success' => __('messa.permission_delete')];
  }

}
