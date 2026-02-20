<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\Auditorium;
use App\Models\AuditoriumEvent;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuditoriumEventController extends Controller {

  protected $user;
  public function __construct() {
    $this->user = JWTAuth::user();
  }

  public function index(Request $request) {

    $user = $this->user;
    $orgIds = $user->getOrgsByPermission('auditorium-index');

    $query = AuditoriumEvent::query()
      ->leftJoin('auditoriums', 'auditorium_events.auditorium_id', '=', 'auditoriums.id')
      ->leftJoin('organizations', 'auditorium_events.org_id', '=', 'organizations.id')
      ->select('auditorium_events.id', 'auditorium_events.event_date', 'auditorium_events.auditorium_id', 'auditorium_events.org_id',
        'auditoriums.name as auditorium_name', 'organizations.name as org_name');

    $itemsPerPage = $request->get('itemsPerPage');
    $sortBy = $request->get('sortBy');
    $sortDesc = $request->get('sortDesc');
    $filter = $request->get('filter');

    if ($sortBy) {
      foreach ($sortBy as $index => $column) {
        $sortDirection = ($sortDesc[$index] == 'true') ? 'DESC' : 'ASC';
        $query = $query->orderBy($column, $sortDirection);
      }
    }

    if ($request->has('date') && !empty($request->get('date'))) {
      $date = \Carbon\Carbon::parse($request->get('date'))->format('Y-m-d');
      $query->where('auditorium_events.event_date', '>=', $date);
    }

    if ($request->has('org_id') && !empty($request->get('org_id'))) {
      $org_id = $request->get('org_id');
      $query->where('org_id', $org_id);
    }

    // if ($filter) {
    //   $query->where('auditoriums.name', 'like', "%{$filter}%");
    // }

    $auditoriumEvents = $query->paginate($itemsPerPage);
    return new DataSetResource($auditoriumEvents);

    // return AuditoriumEvent::all();
  }

  public function show($id) {
    $event = AuditoriumEvent::query()
      ->leftJoin('auditoriums', 'auditorium_events.auditorium_id', '=', 'auditoriums.id')
      ->leftJoin('organizations', 'auditorium_events.org_id', '=', 'organizations.id')
      ->select('auditorium_events.*', 'auditoriums.name as auditorium_name', 'organizations.name as org_name')
      ->where('auditorium_events.id', $id)
      ->firstOrFail();

    $seats = AuditoriumEvent::find($id)
      ->hasMany(\App\Models\AuditoriumEventSeat::class, 'auditorium_event_id')
      ->select("seat_id", "status")
      ->whereNotNull('status')
      ->get();

    $event->seats = $seats->groupBy('status')->map(function ($group) {
      return $group->pluck('seat_id')->toArray();
    });

    $event->timestamp = round(microtime(true) * 1000);

    return response()->json($event);
  }

  public function store(Request $request) {

    $auditorium_id = $request->input('auditorium_id');
    $auditorium = Auditorium::findOrFail($auditorium_id);

    $data = $request->validate([
      'event_date' => 'required|date',

      'auditorium_id' => 'required|exists:auditoriums,id',
      'org_id' => 'required|exists:organizations,id',
    ]);

    $data['config'] = $auditorium->config;

    $event = AuditoriumEvent::create($data);
    return response()->json($event, 201);
  }

  public function update(Request $request, $id) {
    $event = AuditoriumEvent::findOrFail($id);
    $data = $request->validate([
      'event_date' => 'sometimes|date',

      'auditorium_id' => 'sometimes|exists:auditoriums,id',
      'org_id' => 'sometimes|exists:organizations,id',
    ]);

    $auditorium = Auditorium::findOrFail($event->auditorium_id);
    $data['config'] = $auditorium->config;

    $event->update($data);
    return response()->json($event);
  }

  public function destroy($id) {
    $event = AuditoriumEvent::findOrFail($id);
    $event->delete();
    return response()->json(['message' => 'Auditorium event deleted']);
  }
}
