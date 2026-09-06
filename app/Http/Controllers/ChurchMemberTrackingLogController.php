<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Models\Church\ChurchMember;
use App\Models\Church\ChurchMemberTrackingLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChurchMemberTrackingLogController extends Controller
{
    use AppliesOrgPermissionScope;

    protected $user;

    public function __construct()
    {
        $this->user = JWTAuth::user();
    }

    public function trackingLogs(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $query = $member->trackingLogs()->with(['creator', 'churchMember']);

        $page = $request->get('page', 1);
        $itemsPerPage = $request->get('itemsPerPage', 10);
        $sortBy = $request->get('sortBy', ['contact_datetime']);
        $sortDesc = $request->get('sortDesc', ['true']);

        if (! empty($sortBy) && is_array($sortBy)) {
            foreach ($sortBy as $index => $field) {
                $dir = (isset($sortDesc[$index]) && filter_var($sortDesc[$index], FILTER_VALIDATE_BOOLEAN)) ? 'desc' : 'asc';
                $query->orderBy($field, $dir);
            }
        } else {
            $query->orderByDesc('contact_datetime')->orderByDesc('id');
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

    public function allTrackingLogsSummary(Request $request)
    {
        $request->validate([
            'year_month' => 'nullable|date_format:Y-m',
        ]);

        $orgIds = $this->user->getOrgsByPermission('church-member-tracking-logs-all');
        $query = ChurchMemberTrackingLog::query()
            ->with(['creator:id,name,last_name,email']);

        if (empty($orgIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereHas('churchMember', function ($query) use ($orgIds) {
                $query->whereIn('org_id', $orgIds);
            });
        }

        if ($request->filled('year_month')) {
            $yearMonth = $request->input('year_month');
            $query->whereYear('contact_datetime', Carbon::parse($yearMonth)->year)
                ->whereMonth('contact_datetime', Carbon::parse($yearMonth)->month);
        }

        $summary = $query->join('church_members', 'church_members.id', '=', 'church_member_tracking_logs.church_member_id')
            ->select('church_member_tracking_logs.created_by')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(DISTINCT church_member_tracking_logs.church_member_id) as distinct_members')
            ->selectRaw("COUNT(DISTINCT CASE WHEN church_members.status = 'ACTIVO' THEN church_member_tracking_logs.church_member_id END) as active_members")
            ->selectRaw("COUNT(DISTINCT CASE WHEN church_members.status <> 'ACTIVO' THEN church_member_tracking_logs.church_member_id END) as inactive_members")
            ->groupBy('church_member_tracking_logs.created_by')
            ->orderByDesc('total')
            ->get();

        return response()->json($summary);
    }

    public function allTrackingLogs(Request $request)
    {
        $request->validate([
            'year_month' => 'nullable|date_format:Y-m',
            'consolidator_id' => 'required|integer',
        ]);

        $orgIds = $this->user->getOrgsByPermission('church-member-tracking-logs-all');
        $query = ChurchMemberTrackingLog::query()
            ->with([
                'churchMember:id,name,last_name,org_id',
                'creator:id,name,last_name',
            ]);

        if (empty($orgIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereHas('churchMember', function ($query) use ($orgIds) {
                $query->whereIn('org_id', $orgIds);
            });
        }

        if ($request->filled('year_month')) {
            $yearMonth = $request->input('year_month');
            $query->whereYear('contact_datetime', Carbon::parse($yearMonth)->year)
                ->whereMonth('contact_datetime', Carbon::parse($yearMonth)->month);
        }

        $query->where('created_by', $request->integer('consolidator_id'));

        $total = (clone $query)->count();
        $page = $request->integer('page', 1);
        $itemsPerPage = $request->integer('itemsPerPage', 10);
        $sortBy = $request->input('sortBy', ['contact_datetime']);
        $sortDesc = $request->input('sortDesc', [true]);
        $sortableFields = ['contact_datetime', 'created_at', 'medium'];

        if (is_array($sortBy)) {
            foreach ($sortBy as $index => $field) {
                if (! in_array($field, $sortableFields, true)) {
                    continue;
                }
                $direction = isset($sortDesc[$index]) && filter_var($sortDesc[$index], FILTER_VALIDATE_BOOLEAN)
                    ? 'desc'
                    : 'asc';
                $query->orderBy($field, $direction);
            }
        }

        $logs = $query->paginate($itemsPerPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $logs->items(),
            'total' => $total,
        ]);
    }

    public function storeTrackingLog(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $request->validate([
            'contact_datetime' => 'required|date',
            'medium' => 'required|in:whatsapp,llamada,presencial,sms',
            'classification' => 'nullable|in:CONTESTA,NO CONTESTA',
            'description' => 'nullable|string|max:2000',
        ]);

        $log = $member->trackingLogs()->create([
            'contact_datetime' => $request->contact_datetime,
            'medium' => $request->medium,
            'classification' => $request->classification,
            'description' => $request->description,
            'created_by' => $this->user->id,
        ]);

        return response()->json([
            'success' => __('messa.church-member-tracking-log_create'),
            'data' => $log->load('creator'),
        ], 201);
    }

    public function updateTrackingLog(Request $request, $id, $logId)
    {
        $member = $this->findMemberInScope($id);

        $log = $member->trackingLogs()->findOrFail($logId);

        if ((int) $log->created_by !== (int) $this->user->id) {
            return response()->json(['error' => 'No tienes permiso para editar esta interacción'], 403);
        }

        $request->validate([
            'contact_datetime' => 'sometimes|date',
            'medium' => 'sometimes|in:whatsapp,llamada,presencial,sms',
            'classification' => 'nullable|in:CONTESTA,NO CONTESTA',
            'description' => 'nullable|string|max:2000',
        ]);

        $log->update($request->only(['contact_datetime', 'medium', 'classification', 'description']));

        return response()->json([
            'success' => 'Interacción actualizada exitosamente',
            'data' => $log->load('creator'),
        ]);
    }

    public function deleteTrackingLog(Request $request, $id, $logId)
    {
        $member = $this->findMemberInScope($id);

        $log = $member->trackingLogs()->findOrFail($logId);

        if ((int) $log->created_by !== (int) $this->user->id) {
            return response()->json(['error' => 'No tienes permiso para eliminar esta interacción'], 403);
        }

        $log->delete();

        return response()->json(['success' => 'Interacción eliminada exitosamente']);
    }

    private function findMemberInScope($id): ChurchMember
    {
        $query = ChurchMember::query();
        // church-member-all applies org scope based on permitted orgs
        if ($this->hasChurchMemberAll()) {
            $query = $this->applyChurchMemberAllScope($query);
        } else {
            $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');
        }

        return $query->findOrFail($id);
    }

    private function hasChurchMemberAll(): bool
    {
        if (! $this->user) {
            return false;
        }
        try {
            if (method_exists($this->user, 'hasPermissionTo') && $this->user->hasPermissionTo('church-member-all')) {
                return true;
            }
        } catch (\Throwable $e) {
            // fall through to getOrgsByPermission
        }

        return ! empty($this->user->getOrgsByPermission('church-member-all'));
    }

    private function applyChurchMemberAllScope($query)
    {
        $orgIds = $this->user->getOrgsByPermission('church-member-all');
        if (! empty($orgIds)) {
            return $query->whereIn('org_id', $orgIds);
        }

        // No organizations with this permission - return no results
        return $query->whereRaw('1 = 0');
    }
}
