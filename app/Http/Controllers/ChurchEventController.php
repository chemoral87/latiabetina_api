<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\ChurchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChurchEventController extends Controller {
  use AppliesOrgPermissionScope;

  protected $user;
  protected $path = '/church-events/';

  public function __construct() {
    $this->user = JWTAuth::user();
  }

  public function index(Request $request) {
    $filter = $request->get("filter");

    $query = queryServerSide($request, ChurchEvent::query());
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }

    $query = $this->applyOrgPermissionScope($query, $this->user, 'church-event-index');

    $testimonies = $query->paginate($request->get('itemsPerPage'));

    return new DataSetResource($testimonies);

  }

  public function publicIndex(Request $request) {
 
    $start_date = $request->get("start_date");
    $end_date = $request->get("end_date");
    $org_id = $request->get("org_id");

    $org_id_decoded = null;
    if ($org_id) {
      $decoded = base64_decode($org_id, true);
      if ($decoded !== false && is_numeric($decoded)) {
        $org_id_decoded = (int) $decoded;
      }
    }

    $query = queryServerSide($request, ChurchEvent::query());
    $query->where('org_id', $org_id_decoded);

    if ($start_date) {
      $query->whereDate("start_date", ">=", $start_date);
    }

    if ($end_date) {
      $query->whereDate("start_date", "<=", $end_date);
    }

    $events = $query->orderBy('start_date', 'desc')
      ->select([
        'name',
        'slug_name',
        'location',
        'description',
        'start_date',
        'end_date',
        'time_start',
        'url_image',
        'created_at',
      ])
      ->get();

    $events = $events->map(function ($item) {
      return [
        'name' => $item->name,
        'slug_name' => $item->slug_name,
        'location' => $item->location,
        'description' => $item->description,
        'start_date' => $item->start_date,
        'end_date' => $item->end_date,
        'time_start' => $item->time_start,
        'url_image_s3' => $item->url_image_s3,
        'created_at' => $item->created_at,
      ];
    });

    return response()->json($events);
  }

  public function show($id) {
    return ChurchEvent::with(['organization', 'creator'])->findOrFail($id)
      ->makeVisible('url_image')
      ->append('url_image_s3');
  }

  public function store(Request $request) {
    \Illuminate\Support\Facades\Log::info("ChurchEventController@store", $request->all());
    // $orgIds = $this->user->getOrgsByPermission('church-event-index');
    $created_by = $this->user->id;

    $data = $request->validate([
      'name' => 'required|string|max:255',
      'slug_name' => 'nullable|string|unique:church_events,slug_name|max:255',
      'location' => 'nullable|string|max:255',
      'description' => 'nullable|string',
      'start_date' => 'required|date',
      'end_date' => 'nullable|date|after_or_equal:start_date',
      'time_start' => 'nullable|date_format:H:i',
      'url_image' => 'nullable|string',
      'org_id' => 'required|exists:organizations,id',
      // 'created_by' => 'required|exists:users,id',
    ]);

    if ($request->filled('url_image') && str_starts_with($request->url_image, 'data:')) {
      $path = "ORG-" . $data['org_id'] . $this->path;
      $data['url_image'] = saveS3Blob($request->url_image, $path);
    }

    // Generate slug_name if not provided
    if (empty($data['slug_name'])) {
      $yearMonth = date('ymd');
      $data['slug_name'] = Str::slug($data['name']) . '-' . $data['org_id'] . '-' . $yearMonth;
    }

    $data['created_by'] = $created_by;

    $event = ChurchEvent::create($data);
    return response()->json($event->makeVisible('url_image')->append('url_image_s3'), 201);
  }

  public function update(Request $request, $id) {
    $event = ChurchEvent::findOrFail($id);

    $data = $request->validate([
      'name' => 'sometimes|string|max:255',
      'slug_name' => 'nullable|string|unique:church_events,slug_name,' . $id . '|max:255',
      'location' => 'nullable|string|max:255',
      'description' => 'nullable|string',
      'start_date' => 'sometimes|date',
      'end_date' => 'nullable|date|after_or_equal:start_date',
      'time_start' => 'nullable|date_format:H:i',
      'url_image' => 'nullable|string',
      'org_id' => 'sometimes|exists:organizations,id',
      'created_by' => 'sometimes|exists:users,id',
    ]);

    if ($request->filled('url_image') && str_starts_with($request->url_image, 'data:')) {
      $orgId = $data['org_id'] ?? $event->org_id;
      $path = "ORG-" . $orgId . $this->path;
      $data['url_image'] = saveS3Blob($request->url_image, $path, $event->url_image);
    }

    // Regenerate slug_name if name is updated but slug_name is not provided
    if (isset($data['name']) && !isset($data['slug_name'])) {
      $yearMonth = date('ym');
      $orgId = $data['org_id'] ?? $event->org_id;
      $data['slug_name'] = Str::slug($data['name']) . '-' . $orgId . '-' . $yearMonth;
    }

    $event->update($data);
    return response()->json($event->makeVisible('url_image')->append('url_image_s3'));
  }

  public function destroy($id) {
    $event = ChurchEvent::findOrFail($id);
    
    if (!empty($event->url_image)) {
      try {
        \Illuminate\Support\Facades\Storage::disk('s3')->delete($event->url_image);
      } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::warning("Failed to delete S3 image ({$event->url_image}): " . $e->getMessage());
      }
    }

    $event->delete();
    return response()->json(['message' => 'Church event deleted']);
  }
}
