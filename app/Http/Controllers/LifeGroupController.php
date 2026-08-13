<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\LifeGroup\Attendance;
use App\Models\LifeGroup\LifeGroup;
use App\Models\LifeGroup\Person;
use App\Models\LifeGroup\Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class LifeGroupController extends Controller
{
    protected $user;

    public function __construct()
    {
        try {
            $this->user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            $this->user = null;
        }
    }

    public function index(Request $request)
    {
        $query = LifeGroup::query()->with(['sessions', 'leaders']);

        if ($request->filled('filter')) {
            $query->where('name', 'like', '%' . $request->get('filter') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // If the user only has life-group-create (as a leader), filter to their groups
        $user = $this->user;
        if ($user && !$user->hasAnyPermission(['life-group-index'])) {
            $leaderGroupIds = \DB::table('life_group_leaders')
                ->where('user_id', $user->id)
                ->pluck('life_group_id')
                ->toArray();

            $query->where(function ($q) use ($user, $leaderGroupIds) {
                $q->where('created_by', $user->id)
                  ->orWhereIn('id', $leaderGroupIds);
            });
        }

        $query = queryServerSide($request, $query);

        return new DataSetResource($query->paginate($request->get('itemsPerPage', 10)));
    }

    public function show($id)
    {
        $lifeGroup = LifeGroup::with(['sessions.attendance', 'sessions.attendees', 'leaders'])->find($id);
        if (!$lifeGroup) {
            return response()->json(['message' => 'Red de Vida no encontrada'], 404);
        }
        return response()->json($lifeGroup);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'reference' => 'nullable|string',
            'neighborhood' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'start_date' => 'required|date',
            'time' => 'required',
            'day_of_week' => 'required|string',
            'observations' => 'nullable|string',
            'status' => 'nullable|in:active,finished,cancelled',
            'leader_ids' => 'nullable|array',
            'leader_ids.*' => 'integer|exists:users,id',
        ]);

        $validated['created_by'] = $this->user?->id;
        $validated['updated_by'] = $this->user?->id;

        $leaderIds = $validated['leader_ids'] ?? [];
        unset($validated['leader_ids']);

        $lifeGroup = LifeGroup::create($validated);

        // Sync leaders
        if (!empty($leaderIds)) {
            $lifeGroup->leaders()->sync($leaderIds);
        }

        $this->generateSessions($lifeGroup);
        $lifeGroup->load(['sessions', 'leaders']);

        return response()->json(['success' => 'Red de Vida creada exitosamente', 'data' => $lifeGroup], 201);
    }

    public function update(Request $request, $id)
    {
        $lifeGroup = LifeGroup::find($id);
        if (!$lifeGroup) {
            return response()->json(['message' => 'Red de Vida no encontrada'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'reference' => 'nullable|string',
            'neighborhood' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'start_date' => 'required|date',
            'time' => 'required',
            'day_of_week' => 'required|string',
            'observations' => 'nullable|string',
            'status' => 'nullable|in:active,finished,cancelled',
            'leader_ids' => 'nullable|array',
            'leader_ids.*' => 'integer|exists:users,id',
        ]);

        $validated['updated_by'] = $this->user?->id;

        $leaderIds = $validated['leader_ids'] ?? null;
        unset($validated['leader_ids']);

        $lifeGroup->update($validated);

        // Sync leaders if provided
        if ($leaderIds !== null) {
            $lifeGroup->leaders()->sync($leaderIds);
        }

        $lifeGroup->load(['sessions', 'leaders']);

        return response()->json(['success' => 'Red de Vida actualizada exitosamente', 'data' => $lifeGroup]);
    }

    public function destroy($id)
    {
        $lifeGroup = LifeGroup::find($id);
        if (!$lifeGroup) {
            return response()->json(['message' => 'Red de Vida no encontrada'], 404);
        }
        $lifeGroup->delete();
        return response()->json(['success' => 'Red de Vida eliminada exitosamente']);
    }

    public function searchPeople(Request $request)
    {
        $request->validate(['query' => 'required|string|min:2']);
        $query = $request->get('query');
        $people = Person::where('name', 'like', '%' . $query . '%')
            ->orWhere('last_name', 'like', '%' . $query . '%')
            ->orderBy('name')->orderBy('last_name')->limit(20)->get();
        return response()->json($people);
    }

    public function storePerson(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0|max:150',
            'gender' => 'nullable|in:male,female',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|string|max:255',
            'life_group_id' => 'nullable|exists:life_groups,id',
        ]);

        $existing = Person::where('name', $validated['name'])
            ->where('last_name', $validated['last_name'] ?? '')
            ->where('phone', $validated['phone'] ?? '')->first();

        if ($existing) {
            return response()->json(['message' => 'Ya registrada', 'data' => $existing], 409);
        }

        return response()->json(['success' => 'Persona creada', 'data' => Person::create($validated)], 201);
    }

    public function registerAttendance(Request $request, $sessionId)
    {
        $session = Session::find($sessionId);
        if (!$session) {
            return response()->json(['message' => 'Sesion no encontrada'], 404);
        }

        $validated = $request->validate([
            'attendees' => 'required|array',
            'attendees.*.person_id' => 'required|exists:life_group_people,id',
            'attendees.*.type' => 'nullable|in:member,new_guest,convert',
            'attendees.*.observations' => 'nullable|string',
        ]);

        $created = [];
        foreach ($validated['attendees'] as $a) {
            $created[] = Attendance::updateOrCreate(
                ['session_id' => $sessionId, 'person_id' => $a['person_id']],
                ['type' => $a['type'] ?? 'member', 'observations' => $a['observations'] ?? null]
            );
        }

        if ($session->status === 'scheduled') {
            $session->update(['status' => 'completed']);
        }

        return response()->json(['success' => 'Asistencia registrada', 'data' => $created]);
    }

    public function getAttendance($sessionId)
    {
        $session = Session::with(['attendees', 'lifeGroup'])->find($sessionId);
        if (!$session) {
            return response()->json(['message' => 'Sesion no encontrada'], 404);
        }
        return response()->json($session);
    }

    public function updateSession(Request $request, $id)
    {
        $session = Session::find($id);
        if (!$session) {
            return response()->json(['message' => 'Sesion no encontrada'], 404);
        }

        $validated = $request->validate([
            'date' => 'nullable|date',
            'start_time' => 'nullable',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:scheduled,completed,cancelled,rescheduled',
        ]);

        $session->update($validated);
        return response()->json(['success' => 'Sesion actualizada', 'data' => $session]);
    }

    public function dashboard()
    {
        $avgAttendance = Attendance::selectRaw('session_id, COUNT(*) as count')
            ->groupBy('session_id')->get()->avg('count') ?? 0;

        return response()->json([
            'activeGroups' => LifeGroup::where('status', 'active')->count(),
            'finishedGroups' => LifeGroup::where('status', 'finished')->count(),
            'totalPeople' => Person::count(),
            'avgAttendance' => round($avgAttendance, 1),
            'totalSessions' => Session::count(),
            'completedSessions' => Session::where('status', 'completed')->count(),
            'pendingSessions' => Session::where('status', 'scheduled')->count(),
            'newGuests' => Attendance::where('type', 'new_guest')->count(),
            'upcomingSessions' => Session::with('lifeGroup')
                ->where('status', 'scheduled')
                ->where('date', '>=', now())
                ->where('date', '<=', now()->addDays(7))
                ->orderBy('date')->get(),
            'monthlyAttendance' => Attendance::selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as total")
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')->orderBy('month')->get(),
            'attendanceByArea' => LifeGroup::selectRaw("COALESCE(neighborhood, address) as area, COUNT(" . app(Attendance::class)->getTable() . ".id) as total")
                ->join('life_group_sessions', 'life_groups.id', '=', 'life_group_sessions.life_group_id')
                ->join('life_group_attendances', 'life_group_sessions.id', '=', 'life_group_attendances.session_id')
                ->whereNotNull('neighborhood')
                ->groupBy('area')->orderByDesc('total')->get(),
        ]);
    }

    private function generateSessions(LifeGroup $lifeGroup)
    {
        $start = Carbon::parse($lifeGroup->start_date);
        $sessions = [];
        for ($w = 1; $w <= 8; $w++) {
            $d = $start->copy()->addWeeks($w - 1);
            $sessions[] = [
                'life_group_id' => $lifeGroup->id,
                'week_number' => $w,
                'date' => $d->format('Y-m-d'),
                'start_time' => $lifeGroup->time,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        Session::insert($sessions);
    }
}
