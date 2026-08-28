<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\Church\ChurchMember;
use App\Models\Church\ChurchMemberTrackingLog;
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
       ]);

       return response()->json($query->orderByDesc('last_contacted')->get());
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
        $member = $query->findOrFail($id);
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

    // ── Bitácora de seguimiento ─────────────────────────────────────────

    public function trackingLogs(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $query = $member->trackingLogs()->with('creator')->orderByDesc('contact_datetime')->orderByDesc('id');

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
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
        ]);
    }

    public function storeTrackingLog(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $request->validate([
            'contact_datetime' => 'required|date',
            'medium'           => 'required|in:whatsapp,llamada,presencial,sms',
            'classification'   => 'nullable|in:CONTESTA,NO CONTESTA',
            'description'      => 'nullable|string|max:2000',
        ]);

        $log = $member->trackingLogs()->create([
            'contact_datetime' => $request->contact_datetime,
            'medium'           => $request->medium,
            'classification'   => $request->classification,
            'description'      => $request->description,
            'created_by'       => $this->user->id,
        ]);

        return response()->json($log->load('creator'), 201);
    }

    public function updateTrackingLog(Request $request, $id, $logId)
    {
        $member = $this->findMemberInScope($id);

        $log = $member->trackingLogs()->findOrFail($logId);

        $request->validate([
            'contact_datetime' => 'sometimes|date',
            'medium'           => 'sometimes|in:whatsapp,llamada,presencial,sms',
            'classification'   => 'nullable|in:CONTESTA,NO CONTESTA',
            'description'      => 'nullable|string|max:2000',
        ]);

        $log->update($request->only(['contact_datetime', 'medium', 'classification', 'description']));

        return response()->json($log->load('creator'));
    }

    public function deleteTrackingLog(Request $request, $id, $logId)
    {
        $member = $this->findMemberInScope($id);

        $log = $member->trackingLogs()->findOrFail($logId);
        $log->delete();

        return response()->json(['message' => 'Tracking log deleted']);
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
            $q->whereHas('consoSheet', function ($q) {
                $q->where('created_by', $this->user->id);
            })->orWhereHas('consolidators', function ($q) {
                $q->where('users.id', $this->user->id);
            });
        });
    }
}
