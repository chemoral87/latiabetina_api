<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\Song;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SongController extends Controller {
  use AppliesOrgPermissionScope;

  protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }

  public function index(Request $request): DataSetResource {
    $filter = $request->get("filter");

    $query = queryServerSide($request, Song::query());

    if ($filter) {
      $query->where(function ($q) use ($filter) {
        $q->where("title", "like", "%" . $filter . "%")
          ->orWhere("artist", "like", "%" . $filter . "%");
      });
    }

    if ($orgId = $request->get('org_id')) {
      $query->where('org_id', $orgId);
    }

    $query = $this->applyOrgPermissionScope($query, $this->user, 'song-index');

    $songs = $query->paginate($request->get('itemsPerPage'));
    return new DataSetResource($songs);
  }

  public function show(Song $song): JsonResponse {
    return response()->json($song);
  }

  public function store(Request $request): JsonResponse {
    $data = $request->all();

    // Decode base64-encoded org_id if provided (allow creating on encoded id)
    if (isset($data['org_id']) && is_string($data['org_id'])) {
      $decoded = base64_decode($data['org_id'], true);
      if ($decoded !== false && is_numeric($decoded)) {
        $data['org_id'] = (int) $decoded;
      }
    }

    $validator = Validator::make($data, [
      'title' => 'required|string|max:255',
      'artist' => 'nullable|string|max:255',
      'key' => 'nullable|string|max:50',
      'tempo' => 'nullable|string|max:50',
      'content' => 'nullable|array',
      'org_id' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    $data['created_by'] = $this->user->id;

    $song = Song::create($data);

    return response()->json([
      'success' => __('messa.song_create'),
      'data' => $song,
    ], 201);
  }

  public function update(Request $request, Song $song): JsonResponse {
    $data = $request->all();

    $validator = Validator::make($data, [
      'title' => 'sometimes|required|string|max:255',
      'artist' => 'nullable|string|max:255',
      'key' => 'nullable|string|max:50',
      'tempo' => 'nullable|string|max:50',
      'content' => 'nullable|array',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    // Never allow changing the org of an existing song via update
    if (isset($data['org_id'])) {
      unset($data['org_id']);
    }

    $song->update($data);

    return response()->json([
      'success' => __('messa.song_update'),
      'data' => $song,
    ]);
  }

  public function destroy(Song $song): JsonResponse {
    $song->delete();
    return response()->json(null, 204);
  }
}