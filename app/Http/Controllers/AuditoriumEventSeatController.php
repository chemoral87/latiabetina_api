<?php

namespace App\Http\Controllers;

use App\Events\SeatUpdated;
use App\Models\AuditoriumEvent;
use App\Models\AuditoriumEventSeat;
use App\Models\AuditoriumEventSeatLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuditoriumEventSeatController extends Controller {

  protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }

  public function index(Request $request) {
    $user = $this->user;

    $auditoriumEventId = $request->get('auditorium_event_id');
    $last_timestamp = $request->get('last_timestamp');

    $query = AuditoriumEventSeatLog::query()
      ->where('auditorium_event_id', $auditoriumEventId);

    if ($last_timestamp) {
      $query->where('created_at', '>', \Carbon\Carbon::createFromTimestampMs($last_timestamp));
    }

    $seats_log = $query->orderBy('created_at', 'ASC')->get();

    return response()->json([
      'seats_log' => $seats_log,
      'timestamp' => round(microtime(true) * 1000),
    ]);

  }

  public function show($id) {
    $seat = AuditoriumEventSeat::with(['auditoriumEvent', 'creator'])->findOrFail($id);
    return response()->json($seat);
  }

  public function store(Request $request) {
    $user = $this->user;

    $validated = $request->validate([
      'i' => 'required|exists:auditorium_events,id', // auditorium_event_id
      'z' => 'required|array', // seat_ids
      'z.*' => 'required|string',
      's' => 'nullable|string', // status
    ]);

    $auditoriumEventId = $validated['i'];
    $seatIds = $validated['z'];
    $status = $validated['s'] ?? null;

    // Validate event date
    $auditoriumEvent = AuditoriumEvent::findOrFail($auditoriumEventId);
    $eventDate = Carbon::parse($auditoriumEvent->event_date);
    $today = Carbon::now()->subHours(6);

    if ($today->format('Y-m-d') > $eventDate->format('Y-m-d')) {
      return response()->json([
        'warning' => 'Ya nose puede modificar evento',
      ]);
    }

    $updatedSeats = [];

    foreach ($seatIds as $seatId) {
      $seat = AuditoriumEventSeat::updateOrCreate(
        [
          'auditorium_event_id' => $auditoriumEventId,
          'seat_id' => $seatId,
        ],
        [
          'status' => $status,
          'created_by' => $user->id,
        ]
      );
      $updatedSeats[] = $seat;
    }

    // Log the operation
    AuditoriumEventSeatLog::create([
      'auditorium_event_id' => $auditoriumEventId,
      'seat_ids' => $seatIds,
      'status' => $status,
      'created_by' => $user->id,
    ]);

    $timestamp = round(microtime(true) * 1000);

    event(new SeatUpdated($seatIds, $status, $auditoriumEventId, $timestamp));

    return response()->json([

      'success' => 'Asientos actualizados',
      's' => $status,
      'z' => $seatIds,
      't' => $timestamp,
    ]);
  }

  public function update(Request $request, $id) {
    $seat = AuditoriumEventSeat::findOrFail($id);

    $validated = $request->validate([
      'status' => 'nullable|string',
    ]);

    $seat->update($validated);
    return response()->json($seat);
  }

  public function updateBatch(Request $request) {
    $user = $this->user;

    $validated = $request->validate([
      'auditorium_event_id' => 'required|exists:auditorium_events,id',
      'seats' => 'required|array',
      'seats.*.seat_id' => 'required|string',
      'seats.*.status' => 'nullable|string',

    ]);

    $auditoriumEventId = $validated['auditorium_event_id'];
    $seats = $validated['seats'];

    $updated = [];

    foreach ($seats as $seatData) {
      $seat = AuditoriumEventSeat::updateOrCreate(
        [
          'auditorium_event_id' => $auditoriumEventId,
          'seat_id' => $seatData['seat_id'],
        ],
        [
          'status' => substr($seatData['status'], 0, 3) ?? null,
          'created_by' => $user->id,
        ]
      );
      $updated[] = $seat;
    }

    // Fire event for real-time updates
    $seatsData = array_map(function ($seat) {
      return [
        'id' => $seat->seat_id,
        'status' => $seat->status,
      ];
    }, $updated);
    event(new SeatUpdated($seatsData, $auditoriumEventId));

    return response()->json([
      'message' => 'Seats updated successfully',
      'seats' => $updated,
    ]);
  }

  public function destroy($id) {
    $seat = AuditoriumEventSeat::findOrFail($id);
    $seat->delete();
    return response()->json(['message' => 'Seat deleted successfully']);
  }

  public function getByEvent($eventId) {
    $seats = AuditoriumEventSeat::where('auditorium_event_id', $eventId)
      ->with('creator')
      ->get();

    return response()->json($seats);
  }
}
