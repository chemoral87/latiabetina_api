<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller {
  public function index(Request $request) {
    $user = auth('api')->user();
    $permission = 'organization-index';
    $permissions_orgs = $user ? $user->getOrgsByPermission() : [];
    if (!$user || !isset($permissions_orgs[$permission])) {
      return response()->json(['error' => "No tienes permiso para listar organizaciones. Se requiere el permiso: {$permission}"], 403);
    }

    $filter = $request->get("filter");
    $query = queryServerSide($request, Organization::query());
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%")
        ->orWhere("short_code", "like", "%" . $filter . "%");
    }
    $organizations = $query->paginate($request->get('itemsPerPage'));
    return new DataSetResource($organizations);
  }

  public function filter(Request $request) {
    $filter = $request->queryText;
    $ids = isset($request->ids) ? $request->ids : [];
    $permissions = Organization::select("name", "id")
      ->whereNotIn("id", $ids)
      ->where("name", "like", "%" . $filter . "%")
      ->orderBy("name")->paginate(7);
    return $permissions->items();
  }

  public function show($id) {

    $organization = Organization::where("id", $id)->first();
    if ($organization == null) {
      abort(405, 'Organization not found');
    }
    return response()->json($organization);
  }

  public function create(Request $request) {
    $user = auth('api')->user();
    $permission = 'organization-create';
    $permissions_orgs = $user ? $user->getOrgsByPermission() : [];
    if (!$user || !isset($permissions_orgs[$permission])) {
      return response()->json(['error' => "No tienes permiso para crear organizaciones. Se requiere el permiso: {$permission}"], 403);
    }

    $this->validate($request, [
      'name' => 'required|unique:organizations,name',
      'short_code' => 'nullable',
    ]);

    $organization = Organization::create([
      'name' => $request->get('name'),
      'short_code' => $request->get('short_code'),
      'description' => $request->get('description'),
    ]);
    return [
      'success' => __('messa.organization_create'),
      'data' => $organization,
    ];
  }

  public function update(Request $request, $id) {
    $user = auth('api')->user();
    $permission = 'organization-update';
    $permissions_orgs = $user ? $user->getOrgsByPermission() : [];
    if (!$user || !isset($permissions_orgs[$permission])) {
      return response()->json(['error' => "No tienes permiso para actualizar organizaciones. Se requiere el permiso: {$permission}"], 403);
    }

    $this->validate($request, [
      'name' => 'required',
    ]);

    $organization = Organization::find($id);
    $organization->name = $request->get('name');
    $organization->short_code = $request->get('short_code');
    $organization->description = $request->get('description');
    $organization->save();
    return [
      'success' => __('messa.organization_update'),
      'data' => $organization,
    ];
  }

  public function delete($id) {
    $user = auth('api')->user();
    $permission = 'organization-delete';
    $permissions_orgs = $user ? $user->getOrgsByPermission() : [];
    if (!$user || !isset($permissions_orgs[$permission])) {
      return response()->json(['error' => "No tienes permiso para eliminar organizaciones. Se requiere el permiso: {$permission}"], 403);
    }

    $organization = Organization::find($id);
    $organization->delete();
    return ['success' => __('messa.organization_delete')];
  }

}
