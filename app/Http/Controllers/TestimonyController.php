<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\Testimony;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class TestimonyController extends Controller {
  protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }

  public function index(Request $request) {
    $orgIds = $this->user->getOrgsByPermission('testimony-index');

    $filter = $request->get("filter");
    $query = queryServerSide($request, Testimony::query());
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }

    if (empty($orgIds)) {
      // user has no orgs with permission — return empty result
      $query->whereRaw('0 = 1');
    } else {
      $query->whereIn('org_id', $orgIds);
    }

    $testimonies = $query->paginate($request->get('itemsPerPage'));
    return new DataSetResource($testimonies);
  }

  public function show($id) {
    $testimony = Testimony::findOrFail($id);
    return response()->json($testimony);
  }

  public function store(Request $request) {
    $data = $request->all();

    // Normalize single `category` or comma-separated string into `categories` array
    if (isset($data['category']) && !isset($data['categories'])) {
      $data['categories'] = is_array($data['category']) ? $data['category'] : [$data['category']];
      unset($data['category']);
    }

    if (isset($data['categories']) && is_string($data['categories'])) {
      $data['categories'] = array_map('trim', explode(',', $data['categories']));
    }

    $validator = Validator::make($data, [
      'name' => 'required|string|max:255',
      'phone_number' => 'nullable|string|max:50',
      'categories' => 'nullable|array',
      'categories.*' => 'string|max:255',
      'link' => 'nullable|url|max:2048',
      'description' => 'nullable|string',
      // add org_id
      'org_id' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $testimony = Testimony::create($data);

    return response()->json($testimony, 201);
  }

  public function update(Request $request, $id) {
    $testimony = Testimony::findOrFail($id);

    $data = $request->all();

    // Normalize single `category` into `categories` array
    if (isset($data['category']) && !isset($data['categories'])) {
      $data['categories'] = is_array($data['category']) ? $data['category'] : [$data['category']];
      unset($data['category']);
    }

    if (isset($data['categories']) && is_string($data['categories'])) {
      $data['categories'] = array_map('trim', explode(',', $data['categories']));
    }

    $validator = Validator::make($data, [
      'name' => 'sometimes|required|string|max:255',
      'phone_number' => 'nullable|string|max:50',
      'categories' => 'nullable|array',
      'categories.*' => 'string|max:255',
      'link' => 'nullable|url|max:2048',
      'description' => 'nullable|string',
      'approved_by' => 'nullable|integer',
      'approved_date' => 'nullable|date',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $testimony->update($data);

    return response()->json($testimony);
  }

  public function destroy($id) {
    $testimony = Testimony::findOrFail($id);
    $testimony->delete();
    return response()->json(null, 204);
  }
}
