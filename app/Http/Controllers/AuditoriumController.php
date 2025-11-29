<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\Auditorium;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuditoriumController extends Controller {
  public function index(Request $request) {
    $query = Auditorium::query()->leftJoin('organizations', 'auditoriums.org_id', '=', 'organizations.id')
      ->select('auditoriums.*', 'organizations.name as org_name');

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
    if ($filter) {
      $query->where('name', 'like', "%{$filter}%");
    }
    $auditoriums = $query->paginate($itemsPerPage);
    return new DataSetResource($auditoriums);
  }

  public function show(Request $request, $id) {
    $auditorium = Auditorium::findOrFail($id);
    return response()->json($auditorium);
  }

  public function create(Request $request) {
    $userId = JWTAuth::user()->id;
    $this->validate($request, [
      'name' => 'required|unique:auditoriums,name',
      'org_id' => 'required|integer',
      'config' => 'nullable|string',
      'created_by' => 'nullable|integer',
    ]);
    $auditorium = Auditorium::create([
      'name' => $request->input('name'),
      'config' => $request->input('config'),
      'org_id' => $request->input('org_id'),
      'created_by' => $userId,
      'last_updated_by' => $userId,
    ]);
    return ['success' => __('messa.auditorium_create', ['name' => $auditorium->name])];
  }

  public function update(Request $request, $id) {
    $userId = JWTAuth::user()->id;
    $this->validate($request, [
      'name' => 'required',
      'org_id' => 'required|integer',
      'config' => 'nullable|string',
    ]);
    $auditorium = Auditorium::findOrFail($id);
    $auditorium->update([
      'name' => $request->input('name'),
      'config' => $request->input('config'),
      'org_id' => $request->input('org_id'),
      'last_updated_by' => $userId,
    ]);
    return ['success' => __('messa.auditorium_update', ['name' => $auditorium->name])];
  }

  public function delete($id) {
    Auditorium::findOrFail($id)->delete();
    return ['success' => __('messa.auditorium_delete')];
  }
}
