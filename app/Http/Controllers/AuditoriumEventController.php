<?php

namespace App\Http\Controllers;

use App\Models\AuditoriumEvent;
use Illuminate\Http\Request;

class AuditoriumEventController extends Controller {
  public function index() {
    return AuditoriumEvent::all();
  }

  public function show($id) {
    return AuditoriumEvent::findOrFail($id);
  }

  public function store(Request $request) {
    $data = $request->validate([
      'event_date' => 'required|date',
      'config' => 'required|string',
      'auditorium_id' => 'required|exists:auditoriums,id',
      'org_id' => 'required|exists:organizations,id',
    ]);
    $event = AuditoriumEvent::create($data);
    return response()->json($event, 201);
  }

  public function update(Request $request, $id) {
    $event = AuditoriumEvent::findOrFail($id);
    $data = $request->validate([
      'event_date' => 'sometimes|date',
      'config' => 'sometimes|string',
      'auditorium_id' => 'sometimes|exists:auditoriums,id',
      'org_id' => 'sometimes|exists:organizations,id',
    ]);
    $event->update($data);
    return response()->json($event);
  }

  public function destroy($id) {
    $event = AuditoriumEvent::findOrFail($id);
    $event->delete();
    return response()->json(['message' => 'Auditorium event deleted']);
  }
}
