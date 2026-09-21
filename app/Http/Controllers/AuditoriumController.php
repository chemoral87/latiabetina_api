<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\Auditorium\Auditorium;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuditoriumController extends Controller {
  use AppliesOrgPermissionScope;

  protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }
  public function index(Request $request) {
    $query = Auditorium::query()
      ->leftJoin('organizations', 'auditoriums.org_id', '=', 'organizations.id')
      ->select('auditoriums.*', 'organizations.name as org_name')
      ;

    $query = $this->applyOrgPermissionScope($query, $this->user, 'auditorium-index', 'auditoriums.org_id');

    $itemsPerPage = $request->itemsPerPage;
    $sortBy = $request->get('sortBy');
    $sortDesc = $request->get('sortDesc');
    $filter = $request->get('filter');

    if ($sortBy) {
      foreach ($sortBy as $index => $column) {
        $sortDirection = ($sortDesc[$index] == 'true') ? 'DESC' : 'ASC';
        $query = $query->orderBy($column, $sortDirection);
      }
    }

    if ($request->has('org_id') && !empty($request->get('org_id'))) {
      $org_id = $request->get('org_id');
      $query->where('auditoriums.org_id', $org_id);
    }

    if ($filter) {
      $query->where('auditoriums.name', 'like', "%{$filter}%");
    }

    $auditoriums = $query->paginate($itemsPerPage);
    return new DataSetResource($auditoriums);
  }

  public function filter(Request $request) {
    $filter = $request->queryText;
    $ids = isset($request->ids) ? $request->ids : [];
    $query = Auditorium::select("auditoriums.id", "auditoriums.name", "auditoriums.org_id")
      ->whereNotIn("auditoriums.id", $ids)
      ->where("auditoriums.name", "like", "%" . $filter . "%")
      ->orderBy("auditoriums.name");
    $query = $this->applyOrgPermissionScope($query, $this->user, 'auditorium-filter', 'auditoriums.org_id');
    if ($request->has('org_id') && !empty($request->get('org_id'))) {
      $query->where('auditoriums.org_id', $request->get('org_id'));
    }
    $itemsPerPage = (int) $request->get('itemsPerPage', 15);
    if ($itemsPerPage < 1) {
      $itemsPerPage = 15;
    }
    return $query->paginate($itemsPerPage)->items();
  }

  public function show(Request $request, $id) {
    $user = $this->user;
    $auditorium = Auditorium::findOrFail($id);
    $orgIds = $user->getOrgsByPermission('auditorium-index');
    if (!in_array($auditorium->org_id, $orgIds)) {
      abort(403, 'Auditorium access forbidden');
    }
    return response()->json($auditorium);
  }

  public function create(Request $request) {
    $user = $this->user;
    $userId = $user ? $user->id : null;
    $this->validate($request, [
      'name' => 'required',
      'org_id' => 'required|integer',
      'config' => 'nullable|string',
      'created_by' => 'nullable|integer',
      'layout_version' => 'nullable|integer|in:1,2',
    ]);
    $auditorium = Auditorium::create([
      'name' => $request->input('name'),
      'config' => $request->input('config'),
      'org_id' => $request->input('org_id'),
      'created_by' => $userId,
      'last_updated_by' => $userId,
      'layout_version' => $request->input('layout_version', 1),
    ]);
    
    // Fetch the auditorium with org_name for consistent response format
    $auditoriumWithOrg = Auditorium::query()
      ->leftJoin('organizations', 'auditoriums.org_id', '=', 'organizations.id')
      ->select('auditoriums.*', 'organizations.name as org_name')
      ->where('auditoriums.id', $auditorium->id)
      ->first();
    
    return ['success' => __('messa.auditorium_create', ['name' => $auditorium->name]), 'data' => $auditoriumWithOrg];
  }

  public function update(Request $request, $id) {
    $user = $this->user;
    $userId = $user ? $user->id : null;
    $this->validate($request, [
      'name' => 'required',
      'config' => 'nullable|string',
      'layout_version' => 'nullable|integer|in:1,2',
    ]);
    $auditorium = Auditorium::findOrFail($id);
    $update = [
      'name' => $request->input('name'),
      'config' => $request->input('config'),
      'last_updated_by' => $userId,
    ];
    if ($request->has('layout_version')) {
      $update['layout_version'] = $request->input('layout_version');
    }
    $auditorium->update($update);
    return ['success' => __('messa.auditorium_update', ['name' => $auditorium->name]), 'data' => $auditorium];
  }

  public function delete($id) {
    Auditorium::findOrFail($id)->delete();
    return ['success' => __('messa.auditorium_delete')];
  }
}
