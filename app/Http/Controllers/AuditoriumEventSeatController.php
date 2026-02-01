<?php

namespace App\Http\Controllers;

use App\Events\SeatUpdated;
use App\Models\AuditoriumEventSeat;
use App\Models\AuditoriumEventSeatLog;
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
      $query->where('created_at', '>', \Carbon\Carbon::parse($last_timestamp));
    }

    $seats_log = $query->orderBy('created_at', 'ASC')->get();

    return response()->json([
      'seats_log' => $seats_log,
      'timestamp' => now()->toIso8601String(),
    ]);

    // $query = AuditoriumEventSeat::query()
    //   ->with(['auditoriumEvent', 'creator']);

    // $itemsPerPage = $request->get('itemsPerPage');
    // $sortBy = $request->get('sortBy');
    // $sortDesc = $request->get('sortDesc');

    // if ($request->has('auditorium_event_id') && !empty($request->get('auditorium_event_id'))) {
    //   $query->where('auditorium_event_id', $request->get('auditorium_event_id'));
    // }

    // if ($request->has('status') && !empty($request->get('status'))) {
    //   $query->where('status', $request->get('status'));
    // }

    // if ($sortBy) {
    //   foreach ($sortBy as $index => $colum n) {
    //     $sortDirection = ($sortDesc[$index] == 'true') ? 'DESC' : 'ASC';
    //     $query->orderBy($column, $sortDirection);
    //   }
    // }

    // $seats = $query->paginate($itemsPerPage);
    // return new DataSetResource($seats);
  }

  public function show($id) {
    $seat = AuditoriumEventSeat::with(['auditoriumEvent', 'creator'])->findOrFail($id);
    return response()->json($seat);
  }

  public function store(Request $request) {
    $user = $this->user;

    $validated = $request->validate([
      'auditorium_event_id' => 'required|exists:auditorium_events,id',
      'seat_ids' => 'required|array',
      'seat_ids.*' => 'required|string',
      'status' => 'nullable|string',
    ]);

    $auditoriumEventId = $validated['auditorium_event_id'];
    $seatIds = $validated['seat_ids'];
    $status = $validated['status'] ?? null;

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

    // Fire event for real-time updates
    $seatsData = array_map(function ($seat) {
      return [
        'auditorium_event_id' => $seat->auditorium_event_id,
        'seat_id' => $seat->seat_id,
        'status' => $seat->status,
      ];
    }, $updatedSeats);

    $timestamp = now()->toIso8601String();

    event(new SeatUpdated($seatsData, $auditoriumEventId, $timestamp));

    return response()->json([

      'success' => 'Asientos actualizados',
      'seats' => $updatedSeats,
      'timestamp' => $timestamp,
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
