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
    $status = $request->get("status");

    $query = queryServerSide($request, Testimony::query());
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }

    if ($status) {
      $query->where("status", $status);
    } else {
      $query->whereNull("status");
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

  // Public endpoint: list all approved testimonies (no org restriction)
  public function publicIndex(Request $request) {
    $filter = $request->get("filter");
    $org_id = $request->get("org_id");

    // Decode base64 org_id if provided
    $org_id_decoded = null;
    if ($org_id) {
      $decoded = base64_decode($org_id, true);
      if ($decoded !== false && is_numeric($decoded)) {
        $org_id_decoded = (int) $decoded;
      }
    }

    $query = queryServerSide($request, Testimony::query());
    $query->where('status', 'approved');

    $query->where('org_id', $org_id_decoded);

    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }

    $testimonies = $query->orderBy('created_at', 'desc')
      ->select(['name', 'categories', 'link', 'description', 'created_at'])
      ->paginate($request->get('itemsPerPage'));

    $testimonies->getCollection()->transform(function ($item) {
      return [
        'name' => $item->name,
        'categories' => $item->categories,
        'link' => $item->link,
        'description' => $item->description,
        'created_at' => $item->created_at,
      ];
    });

    return response()->json($testimonies);
  }

  public function show($id) {
    $testimony = Testimony::findOrFail($id);

    $statusUser = null;
    if ($testimony->status_by) {
      $statusUser = \App\Models\User::find($testimony->status_by);
    }

    $testimony->setAttribute('status_username', $statusUser ? ($statusUser->name ?? null) : null);

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

    // Decode base64-encoded org_id if provided (allow updating org via encoded id)
    if (isset($data['org_id']) && is_string($data['org_id'])) {
      $decoded = base64_decode($data['org_id'], true);
      if ($decoded !== false && is_numeric($decoded)) {
        $data['org_id'] = (int) $decoded;
      }
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
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    // Never allow changing the org of an existing testimony via update
    if (isset($data['org_id'])) {
      unset($data['org_id']);
    }

    $testimony->update($data);

    return ['success' => __('messa.role_update'), 'testimony' => $testimony];

  }

  public function updateStatus(Request $request, $id) {

    $validator = Validator::make($request->all(), [
      'status' => 'required|in:approved,rejected',
    ]);

    switch ($request->get('status')) {
      case 'approved':
        $success_message = __('messa.testimony_approved');
        break;
      case 'rejected':
      default:
        $success_message = __('messa.testimony_rejected');
        break;
    }

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $testimony = Testimony::findOrFail($id);
    $testimony->status = $request->get('status');
    $testimony->status_by = $this->user->id;
    $testimony->updated_at = now();
    $testimony->save();

    $statusUser = null;
    if ($testimony->status_by) {
      $statusUser = \App\Models\User::find($testimony->status_by);
    }

    $testimony->setAttribute('status_username', $statusUser ? ($statusUser->name ?? null) : null);

    return [
      'success' => $success_message,
      'testimony' => $testimony,
    ];
    // return response()->json($testimony);
  }

  public function destroy($id) {
    $testimony = Testimony::findOrFail($id);
    $testimony->delete();
    return response()->json(null, 204);
  }
}
