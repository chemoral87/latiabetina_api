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
        return $this->listMedals($this->findMemberInScope($id));
    }

    public function indexMy(Request $request, $id)
    {
        return $this->listMedals($this->findMemberInMyScope($id));
    }

    public function store(Request $request, $id)
    {
        return $this->createMedal($request, $this->findMemberInScope($id));
    }

    public function storeMy(Request $request, $id)
    {
        return $this->createMedal($request, $this->findMemberInMyScope($id));
    }

    public function destroy(Request $request, $id, $medalId)
    {
        return $this->removeMedal($this->findMemberInScope($id), $medalId);
    }

    public function destroyMy(Request $request, $id, $medalId)
    {
        return $this->removeMedal($this->findMemberInMyScope($id), $medalId);
    }

    public function logs(Request $request, $id)
    {
        return $this->listMedalLogs($this->findMemberInScope($id));
    }

    public function logsMy(Request $request, $id)
    {
        return $this->listMedalLogs($this->findMemberInMyScope($id));
    }

    private function listMedals(ChurchMember $member)
    {
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

    private function createMedal(Request $request, ChurchMember $member)
    {
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

    private function removeMedal(ChurchMember $member, $medalId)
    {
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

    private function listMedalLogs(ChurchMember $member)
    {
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
            return $this->applyChurchMemberAllScope($query)->findOrFail($id);
        }
        $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');
        return $query->findOrFail($id);
    }

    private function findMemberInMyScope($id): ChurchMember
    {
        $query = ChurchMember::query();
        $query = $this->applyOrgPermissionScope($query, $this->user, 'church-member-my-index');
        $query = $this->applyMineScope($query);
        return $query->findOrFail($id);
    }

    private function applyMineScope($query)
    {
        $groupMateIds = method_exists($this->user, 'groupMateIds') ? $this->user->groupMateIds() : [];

        return $query->where(function ($q) use ($groupMateIds) {
            $q->where('created_by', $this->user->id)
              ->orWhere(function ($q) use ($groupMateIds) {
                if (!empty($groupMateIds)) {
                    $q->whereIn('created_by', $groupMateIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
              })->orWhereHas('consoSheet', function ($q) {
                $q->where('created_by', $this->user->id);
              })->orWhereHas('consolidators', function ($q) {
                $q->where('users.id', $this->user->id);
              })->orWhereHas('consolidators', function ($q) use ($groupMateIds) {
                if (!empty($groupMateIds)) {
                    $q->whereIn('users.id', $groupMateIds);
                } else {
                    $q->whereRaw('1 = 0');
                }
              });
        });
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
