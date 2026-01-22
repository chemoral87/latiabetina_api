<?php

namespace App\Http\Controllers;

use App\Models\ChurchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChurchEventController extends Controller {
  public function index() {
    return ChurchEvent::with(['organization', 'creator'])->get();
  }

  public function show($id) {
    return ChurchEvent::with(['organization', 'creator'])->findOrFail($id);
  }

  public function store(Request $request) {
    $data = $request->validate([
      'name' => 'required|string|max:255',
      'slug_name' => 'nullable|string|unique:church_events,slug_name|max:255',
      'location' => 'nullable|string|max:255',
      'description' => 'nullable|string',
      'start_date' => 'required|date',
      'end_date' => 'nullable|date|after_or_equal:start_date',
      'time_start' => 'nullable|date_format:H:i:s',
      'url_image' => 'nullable|string|max:255',
      'org_id' => 'required|exists:organizations,id',
      'created_by' => 'required|exists:users,id',
    ]);

    // Generate slug_name if not provided
    if (empty($data['slug_name'])) {
      $yearMonth = date('ym');
      $data['slug_name'] = Str::slug($data['name']) . '-' . $data['org_id'] . '-' . $yearMonth;
    }

    $event = ChurchEvent::create($data);
    return response()->json($event, 201);
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
      'time_start' => 'nullable|date_format:H:i:s',
      'url_image' => 'nullable|string|max:255',
      'org_id' => 'sometimes|exists:organizations,id',
      'created_by' => 'sometimes|exists:users,id',
    ]);

    // Regenerate slug_name if name is updated but slug_name is not provided
    if (isset($data['name']) && !isset($data['slug_name'])) {
      $yearMonth = date('ym');
      $orgId = $data['org_id'] ?? $event->org_id;
      $data['slug_name'] = Str::slug($data['name']) . '-' . $orgId . '-' . $yearMonth;
    }

    $event->update($data);
    return response()->json($event);
  }

  public function destroy($id) {
    $event = ChurchEvent::findOrFail($id);
    $event->delete();
    return response()->json(['message' => 'Church event deleted']);
  }
}
