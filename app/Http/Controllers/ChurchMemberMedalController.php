<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Models\Church\ChurchMember;
use App\Models\Church\ChurchMemberMedalLog;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChurchMemberMedalController extends Controller
{
    use AppliesOrgPermissionScope;

    protected $user;

    public function __construct()
    {
        $this->user = JWTAuth::user();
    }

    public function index(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $medals = $member->medals()
            ->with('creator')
            ->orderByDesc('id')
            ->get()
            ->map(function ($medal) {
                if (is_string($medal->description)) {
                    $medal->description = json_decode($medal->description, true);
                }
                return $medal;
            });

        return response()->json($medals);
    }

    public function store(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $request->validate([
            'medal'       => 'required|string|max:255',
            'description' => 'nullable|array',
        ]);

        $description = $request->description;
        if (is_array($description)) {
            $description = json_encode($description);
        }

        $medal = $member->medals()->create([
            'medal'       => $request->medal,
            'description' => $description,
            'created_by'  => $this->user->id,
        ]);

        ChurchMemberMedalLog::create([
            'church_member_id' => $member->id,
            'medal'            => $request->medal,
            'description'      => $description,
            'action'           => 'assigned',
            'changed_by'       => $this->user->id,
        ]);

        return response()->json($medal->load('creator'), 201);
    }

    public function destroy(Request $request, $id, $medalId)
    {
        $member = $this->findMemberInScope($id);

        $medal = $member->medals()->findOrFail($medalId);

        ChurchMemberMedalLog::create([
            'church_member_id' => $member->id,
            'medal'            => $medal->medal,
            'description'      => $medal->description,
            'action'           => 'removed',
            'changed_by'       => $this->user->id,
        ]);

        $medal->delete();

        return response()->json(['success' => 'Medalla removida exitosamente']);
    }

    public function logs(Request $request, $id)
    {
        $member = $this->findMemberInScope($id);

        $logs = ChurchMemberMedalLog::where('church_member_id', $member->id)
            ->with('changer:id,name,last_name')
            ->orderByDesc('id')
            ->get()
            ->map(function ($log) {
                if (is_string($log->description)) {
                    $log->description = json_decode($log->description, true);
                }
                return $log;
            });

        return response()->json($logs);
    }

    private function findMemberInScope($id): ChurchMember
    {
        $query = ChurchMember::query();
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
        return $query->whereRaw('1 = 0');
    }
}
