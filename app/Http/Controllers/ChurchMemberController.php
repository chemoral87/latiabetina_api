<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Church\ChurchMember;
use App\Models\Church\ChurchMemberConsolidatorLog;
use App\Models\Church\ChurchMemberTrackingLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChurchMemberController extends Controller
{
    use AppliesOrgPermissionScope;

    protected $user;

    protected string $path = '/church-members/';

    public function __construct() {
        $this->user = JWTAuth::user();
    }

    public function index(Request $request)
    {
        $query = ChurchMember::query();

        $hasAll = $this->hasChurchMemberAll();

        if ($hasAll) {
            // For church-member-all, apply org scope based on permitted orgs
            $query = $this->applyChurchMemberAllScope($query);
        } else {
            // For regular permissions, apply org permission scope
            $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');
        }

        if (!$hasAll && $request->boolean('mine')) {
            $query = $this->applyMineScope($query);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('org_id') && !empty($request->org_id)) {
            $query->where('org_id', $request->org_id);
        }

        if ($request->has('conso_sheet_id') && !empty($request->conso_sheet_id)) {
            $query->where('conso_sheet_id', $request->conso_sheet_id);
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
               ->selectRaw('MAX(contact_datetime)')
               ->whereColumn('church_member_id', 'church_members.id'),
           'last_contacted_by' => ChurchMemberTrackingLog::query()
               ->leftJoin('users', 'church_member_tracking_logs.created_by', '=', 'users.id')
               ->selectRaw('CASE WHEN users.id IS NOT NULL THEN CONCAT(users.name, " ", users.last_name) ELSE "Sistema" END as name')
               ->whereColumn('church_member_tracking_logs.church_member_id', 'church_members.id')
               ->orderByDesc('church_member_tracking_logs.contact_datetime')
               ->orderByDesc('church_member_tracking_logs.id')
               ->limit(1),
           'assigned_by' => ChurchMemberConsolidatorLog::query()
               ->leftJoin('users as assigner', 'church_member_consolidator_logs.changed_by', '=', 'assigner.id')
               ->selectRaw('CONCAT(assigner.name, " ", assigner.last_name)')
               ->whereColumn('church_member_consolidator_logs.church_member_id', 'church_members.id')
               ->where('church_member_consolidator_logs.consolidator_id', $this->user->id)
               ->where('church_member_consolidator_logs.action', 'assigned')
               ->orderByDesc('church_member_consolidator_logs.id')
               ->limit(1),
       ]);
        $query->with('creator:id,name,last_name');

        $sortBy = $request->get('sortBy');
        $sortDesc = $request->get('sortDesc');

        $allowedSortColumns = ['id', 'name', 'last_name', 'cellphone', 'status', 'org_id', 'last_contacted', 'created_at'];

        if (!empty($sortBy)) {
            $columns = is_array($sortBy) ? $sortBy : [$sortBy];
            $directions = (!empty($sortDesc) && is_array($sortDesc)) ? $sortDesc : [];
            foreach ($columns as $index => $column) {
                if (!is_string($column) || !in_array($column, $allowedSortColumns)) {
                    continue;
                }
                $dir = (isset($directions[$index]) && filter_var($directions[$index], FILTER_VALIDATE_BOOLEAN)) ? 'desc' : 'asc';
                $query->orderBy($column, $dir);
            }
        } else {
            $query->orderByDesc('last_contacted');
        }

        return response()->json($query->get());
    }

    public function show($id)
    {
        $query = ChurchMember::query();
        // church-member-all applies org scope based on permitted orgs
        if ($this->hasChurchMemberAll()) {
            $query = $this->applyChurchMemberAllScope($query);
        } else {
            $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');
            $query = $this->applyMineScope($query);
        }
        $member = $query->with('creator:id,name,last_name')
            ->with(['consolidators:id,name,last_name,second_last_name,email'])
            ->find($id);
        if (!$member) {
            abort(404, 'Miembro no encontrado o no tienes acceso a esta organización.');
        }
        return response()->json($member);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'org_id'              => 'required|exists:organizations,id',
            'conso_sheet_id'      => 'sometimes|nullable|exists:conso_sheets,id',
            'name'                => 'required|string|max:255',
            'last_name'           => 'required|string|max:255',
            'second_last_name'    => 'nullable|string|max:255',
            'cellphone'           => 'nullable|string|max:50',
            'years_old'           => 'nullable|integer|min:0|max:150',
            'number_of_children'  => 'nullable|integer|min:0',
            'marriage_status'     => 'nullable|string|max:50',
            'address'             => 'nullable|string|max:500',
            'url_image'           => 'nullable|string',
            'status'              => 'nullable|in:ACTIVO,NO CONTESTA,NO MOLESTAR,VISITA',
        ]);

        if ($request->filled('url_image') && str_starts_with($request->url_image, 'data:')) {
            $path = "ORG-{$request->org_id}{$this->path}";
            $treatedImage = treatImage($request->url_image, 95);
            $data['url_image'] = saveS3Blob($treatedImage, $path);
        }

        // Set default status if not provided
if (!isset($data['status'])) {
    $data['status'] = 'ACTIVO';
}

$data['created_by'] = $this->user->id;

$member = ChurchMember::create($data);

        // ── WhatsApp bienvenida (no bloquea el response) ───────────────────
        // Se envía: "hola {name}, Bienvenido a la Iglesia Avivamiento Monterrey."
        // Solo si hay cellphone. Usa la misma infraestructura que WhatsAppController.php:43 (queue whatsapp)
        if (!empty($member->cellphone)) {
            try {
                $welcome = "hola {$member->name} {$member->last_name}, Bienvenido a la Iglesia Avivamiento Monterrey.";
                $botUrl      = config('services.whatsapp.bot_url');
                $botPassword = config('services.whatsapp.password');
                $isDebug     = config('services.whatsapp.debug', false);

                if (!empty($botUrl) && !empty($botPassword)) {
                    SendWhatsAppMessageJob::dispatch(
                        $member->cellphone,
                        $welcome,
                        null, // mediaUrl
                        $botUrl,
                        $botPassword,
                        $isDebug
                    );
                } else {
                    Log::warning('WhatsApp welcome skipped: bot_url/bot_password not configured', [
                        'member_id' => $member->id,
                    ]);
                }
            } catch (\Throwable $e) {
                // No romper la creación si falla el dispatch
                Log::error('WhatsApp welcome dispatch failed: ' . $e->getMessage(), [
                    'member_id' => $member->id,
                    'phone'     => $member->cellphone,
                ]);
            }
        }

        return response()->json([
            'success' => __('messa.church-member_create'),
            'data' => $member->append('url_image_s3'), // Includes status field
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $member = ChurchMember::findOrFail($id);

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'last_name'          => 'required|string|max:255',
            'second_last_name'   => 'nullable|string|max:255',
            'cellphone'          => 'nullable|string|max:50',
            'years_old'          => 'nullable|integer|min:0|max:150',
            'number_of_children' => 'nullable|integer|min:0',
            'marriage_status'    => 'nullable|string|max:50',
            'address'            => 'nullable|string|max:500',
            'url_image'          => 'nullable|string',
        ]);

        if ($request->filled('url_image') && str_starts_with($request->url_image, 'data:')) {
            $path = "ORG-{$member->org_id}{$this->path}";
            $treatedImage = treatImage($request->url_image, 95);
            $data['url_image'] = saveS3Blob($treatedImage, $path, $member->url_image);
        }

        $member->update($data);
        return response()->json([
            'success' => __('messa.church-member_update'),
            'data' => $member->append('url_image_s3'),
        ]);
    }

    public function delete($id)
    {
        $member = ChurchMember::findOrFail($id);
        $member->delete();
        return response()->json(['success' => __('messa.church-member_delete')]);
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

        return response()->json([
            'success' => __('messa.church-member_status_update'),
            'data' => $member,
        ]);
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

    // ── Consolidadores ────────────────────────────────────────────────

    public function consolidators($id)
    {
        $member = $this->findMemberInScope($id);

        $consolidators = $member->consolidators()
            ->select('id', 'name', 'last_name', 'second_last_name', 'email')
            ->get();

        return response()->json($consolidators);
    }

    public function syncConsolidators(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        // Some clients accidentally double-wrap the payload as
        // {"consolidator_ids": {"consolidator_ids": [...]}} — unwrap it before validating.
        $payload = $request->input('consolidator_ids');
        if (is_array($payload) && array_key_exists('consolidator_ids', $payload)) {
            $request->merge(['consolidator_ids' => $payload['consolidator_ids']]);
        }

        // Validate input
        $request->validate([
            'consolidator_ids'   => 'nullable|array',
            'consolidator_ids.*' => 'integer|exists:users,id',
        ]);

        // Validate that each user has the 'conso-sheet-index' permission for this member's org.
        $candidateIds = collect($request->consolidator_ids)->unique()->values();
        $invalidUserIds = $candidateIds->filter(function ($userId) use ($member) {
            $candidate = User::find($userId);
            if (!$candidate) {
                return true;
            }
            $orgIds = $candidate->getOrgsByPermission('conso-sheet-index');
            return !in_array($member->org_id, $orgIds);
        });
        if ($invalidUserIds->isNotEmpty()) {
            return response()->json(['error' => 'Some users do not have the required permission.'], 400);
        }

        // Get current consolidator IDs before sync
        $oldIds = $member->consolidators()->pluck('users.id')->toArray();
        $newIds = $candidateIds->toArray();

        $member->consolidators()->sync($newIds);

        // Log assignments and unassignments
        $addedIds = array_values(array_diff($newIds, $oldIds));
        $removedIds = array_values(array_diff($oldIds, $newIds));

        foreach ($addedIds as $consolidatorId) {
            ChurchMemberConsolidatorLog::create([
                'church_member_id' => $member->id,
                'consolidator_id'  => $consolidatorId,
                'action'           => 'assigned',
                'changed_by'       => $this->user->id,
            ]);
        }

        foreach ($removedIds as $consolidatorId) {
            ChurchMemberConsolidatorLog::create([
                'church_member_id' => $member->id,
                'consolidator_id'  => $consolidatorId,
                'action'           => 'unassigned',
                'changed_by'       => $this->user->id,
            ]);
        }

        $consolidators = $member->consolidators()
            ->select('id', 'name', 'last_name', 'second_last_name', 'email')
            ->get();

        return response()->json([
            'success' => 'Consolidadores actualizados',
            'data'    => $consolidators,
        ]);
    }

    public function consolidatorLogs(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $logs = ChurchMemberConsolidatorLog::where('church_member_id', $member->id)
            ->with('consolidator:id,name,last_name,email')
            ->with('changer:id,name,last_name')
            ->orderByDesc('id')
            ->get();

        return response()->json($logs);
    }

    public function consolidatorLogsIndex(Request $request)
    {
        $query = ChurchMemberConsolidatorLog::query()
            ->with('consolidator:id,name,last_name,email')
            ->with('changer:id,name,last_name')
            ->with('churchMember:id,name,last_name,org_id');

        // Filter by member's org based on permission
        if ($this->hasChurchMemberAll()) {
            $orgIds = $this->user->getOrgsByPermission('church-member-all');
            if (!empty($orgIds)) {
                $query->whereHas('churchMember', function ($q) use ($orgIds) {
                    $q->whereIn('org_id', $orgIds);
                });
            }
        } else {
            $orgIds = $this->user->getOrgsByPermission('conso-sheet-index');
            if (!empty($orgIds)) {
                $query->whereHas('churchMember', function ($q) use ($orgIds) {
                    $q->whereIn('org_id', $orgIds);
                });
            }
        }

        if ($request->has('filter') && !empty($request->filter)) {
            $term = '%' . $request->filter . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('consolidator', function ($q2) use ($term) {
                    $q2->where('name', 'like', $term)
                       ->orWhere('last_name', 'like', $term)
                       ->orWhere('email', 'like', $term);
                })->orWhereHas('changer', function ($q2) use ($term) {
                    $q2->where('name', 'like', $term)
                       ->orWhere('last_name', 'like', $term);
                })->orWhereHas('churchMember', function ($q2) use ($term) {
                    $q2->where('name', 'like', $term)
                       ->orWhere('last_name', 'like', $term);
                });
            });
        }

        if ($request->has('action') && !empty($request->action)) {
            $query->where('action', $request->action);
        }

        $page = $request->get('page', 1);
        $itemsPerPage = $request->get('itemsPerPage', 10);
        $sortBy = $request->get('sortBy', ['id']);
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
        ]);
    }

    public function trackingLogsIndex(Request $request)
    {
        $query = ChurchMemberTrackingLog::query()
            ->with('churchMember:id,name,last_name,org_id')
            ->where('created_by', $this->user->id);

        // Filter by member's org based on permission
        if ($this->hasChurchMemberAll()) {
            $orgIds = $this->user->getOrgsByPermission('church-member-all');
            if (!empty($orgIds)) {
                $query->whereHas('churchMember', function ($q) use ($orgIds) {
                    $q->whereIn('org_id', $orgIds);
                });
            }
        } else {
            $orgIds = $this->user->getOrgsByPermission('conso-sheet-index');
            if (!empty($orgIds)) {
                $query->whereHas('churchMember', function ($q) use ($orgIds) {
                    $q->whereIn('org_id', $orgIds);
                });
            }
        }

        if ($request->has('filter') && !empty($request->filter)) {
            $term = '%' . $request->filter . '%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('churchMember', function ($q2) use ($term) {
                    $q2->where('name', 'like', $term)
                       ->orWhere('last_name', 'like', $term);
                })->orWhere('medium', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        if ($request->has('medium') && !empty($request->medium)) {
            $query->where('medium', $request->medium);
        }

        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('contact_datetime', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('contact_datetime', '<=', $request->date_to . ' 23:59:59');
        }

        $page = $request->get('page', 1);
        $itemsPerPage = $request->get('itemsPerPage', 10);
        $sortBy = $request->get('sortBy', ['contact_datetime']);
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
        ]);
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
        if (!$this->user) {
            return false;
        }
        try {
            if (method_exists($this->user, 'hasPermissionTo') && $this->user->hasPermissionTo('church-member-all')) {
                return true;
            }
        } catch (\Throwable $e) {
            // fall through to getOrgsByPermission
        }
        return !empty($this->user->getOrgsByPermission('church-member-all'));
    }

    private function applyChurchMemberAllScope($query)
    {
        $orgIds = $this->user->getOrgsByPermission('church-member-all');
        if (!empty($orgIds)) {
            return $query->whereIn('org_id', $orgIds);
        }
        // No organizations with this permission - return no results
        return $query->whereRaw('1 = 0');
    }

    private function applyMineScope($query)
    {
        return $query->where(function ($q) {
            $q->where('created_by', $this->user->id)
              ->orWhereHas('consoSheet', function ($q) {
                $q->where('created_by', $this->user->id);
            })->orWhereHas('consolidators', function ($q) {
                $q->where('users.id', $this->user->id);
            });
        });
    }
}
