<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Models\Church\ChurchMember;
use App\Models\Church\ChurchMemberTrackingLog;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChurchMemberController extends Controller
{
    use AppliesOrgPermissionScope;

    protected $user;

    public function __construct() {
        $this->user = JWTAuth::user();
    }

    public function index(Request $request)
    {
        $query = ChurchMember::query();

        $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');

        if ($request->boolean('mine')) {
            $query = $this->applyMineScope($query);
        }

        if ($request->has('conso_sheet_id') && !empty($request->conso_sheet_id)) {
            $query->where('conso_sheet_id', $request->conso_sheet_id);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('org_id') && !empty($request->org_id)) {
            $query->where('org_id', $request->org_id);
        }

        if ($request->has('filter') && !empty($request->filter)) {
            $term = '%' . $request->filter . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('last_name', 'like', $term)
                  ->orWhere('cellphone', 'like', $term);
            });
        }

        $query->addSelect([
            'last_contacted' => ChurchMemberTrackingLog::query()
                ->selectRaw('MAX(contact_date)')
                ->whereColumn('church_member_id', 'church_members.id'),
        ]);

        return response()->json($query->orderByDesc('last_contacted')->get());
    }

    public function show($id)
    {
        $query = ChurchMember::query();
        $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');
        $query = $this->applyMineScope($query);
        $member = $query->findOrFail($id);
        return response()->json($member);
    }

    public function create(Request $request)
    {
        $request->validate([
            'org_id'         => 'required|exists:organizations,id',
            'conso_sheet_id' => 'sometimes|nullable|exists:conso_sheets,id',
            'name'           => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
        ]);

        $member = ChurchMember::create($request->all());
        return response()->json($member, 201);
    }

    public function update(Request $request, $id)
    {
        $member = ChurchMember::findOrFail($id);

        $request->validate([
            'name'      => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        $member->update($request->all());
        return response()->json($member);
    }

    public function delete($id)
    {
        $member = ChurchMember::findOrFail($id);
        $member->delete();
        return response()->json(['message' => 'Church member deleted successfully']);
    }

    // ── Bitácora de seguimiento ─────────────────────────────────────────

    public function trackingLogs(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $query = $member->trackingLogs()->with('creator')->orderByDesc('contact_date')->orderByDesc('id');

        $page = $request->get('page', 1);
        $itemsPerPage = $request->get('itemsPerPage', 10);
        $sortBy = $request->get('sortBy', ['contact_date']);
        $sortDesc = $request->get('sortDesc', [true]);

        if (!empty($sortBy) && is_array($sortBy)) {
            foreach ($sortBy as $index => $field) {
                $dir = (isset($sortDesc[$index]) && filter_var($sortDesc[$index], FILTER_VALIDATE_BOOLEAN)) ? 'desc' : 'asc';
                $query->orderBy($field, $dir);
            }
        }

        $total = $query->count();
        $logs = $query->paginate($itemsPerPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $logs->items(),
            'total' => $total,
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
        ]);
    }

    public function storeTrackingLog(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $request->validate([
            'contact_date'   => 'required|date',
            'medium'         => 'required|in:whatsapp,llamada,presencial,sms',
            'classification' => 'nullable|in:CONTESTA,NO CONTESTA',
            'description'    => 'nullable|string|max:2000',
        ]);

        $log = $member->trackingLogs()->create([
            'contact_date'   => $request->contact_date,
            'medium'         => $request->medium,
            'classification' => $request->classification,
            'description'    => $request->description,
            'created_by'     => $this->user->id,
        ]);

        return response()->json($log->load('creator'), 201);
    }

    // ── Clasificación (estado) ──────────────────────────────────────────

    public function updateStatus(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $request->validate([
            'status' => 'required|in:ACTIVO,NO CONTESTA,NO MOLESTAR,VISITA',
            'reason' => 'nullable|string|max:1000',
        ]);

        $newStatus = $request->status;
        $oldStatus = $member->status ?? 'ACTIVO';

        if ($oldStatus !== $newStatus) {
            $member->status = $newStatus;
            $member->save();

            $member->statusLogs()->create([
                'new_status' => $newStatus,
                'reason'     => $request->reason,
                'changed_by' => $this->user->id,
            ]);
        }

        return response()->json($member);
    }

    public function statusLogs(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $logs = $member->statusLogs()
            ->with('changer')
            ->orderByDesc('id')
            ->get();

        return response()->json($logs);
    }

    // ── Medallas ─────────────────────────────────────────────────────────

    public function medals(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $medals = $member->medals()
            ->with('creator')
            ->orderByDesc('id')
            ->get();

        return response()->json($medals);
    }

    public function storeMedal(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $request->validate([
            'medal'       => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $medal = $member->medals()->create([
            'medal'       => $request->medal,
            'description' => $request->description,
            'created_by'  => $this->user->id,
        ]);

        return response()->json($medal->load('creator'), 201);
    }

    private function findMemberInScope($id): ChurchMember
    {
        $query = ChurchMember::query();
        $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');
        return $query->findOrFail($id);
    }

    private function applyMineScope($query)
    {
        return $query->where(function ($q) {
            $q->whereHas('consoSheet', function ($q) {
                $q->where('created_by', $this->user->id);
            })->orWhereHas('consolidators', function ($q) {
                $q->where('users.id', $this->user->id);
            });
        });
    }
}