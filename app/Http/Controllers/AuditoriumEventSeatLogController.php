<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AuditoriumEventSeatLog;
use App\Models\User;

class AuditoriumEventSeatLogController extends Controller
{
    public function index(Request $request)
    {
        $sectionPrefix = $request->input('section_prefix');
        $eventId = $request->input('auditorium_event_id');
        $query = AuditoriumEventSeatLog::where("auditorium_event_id", $eventId);

        if ($sectionPrefix) {
            $query->whereRaw(
                "JSON_SEARCH(seat_ids, 'one', ?, NULL, '$[*]') IS NOT NULL",
                [$sectionPrefix . '%']
            );
        }

        $logs = $query->orderBy('created_at', 'asc')
            ->get(['seat_ids', 'status', 'created_by', 'created_at'])
            ->map(function ($log) {
                return [
                    'seat_ids'   => $log->seat_ids,
                    'status'     => $log->status,
                    'created_by' => $log->created_by,
                    'created_at' => $log->created_at->getTimestampMs(),
                ];
            });

        $userIds = $logs->pluck('created_by')->unique()->filter()->values();
        $users = User::whereIn('id', $userIds)->get(['id', 'name', 'last_name']);

        return response()->json([
            'seatsLog' => $logs,
            'users'    => $users,
        ]);
    }
}
