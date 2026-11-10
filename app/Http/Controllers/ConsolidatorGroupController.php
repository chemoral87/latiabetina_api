<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ConsolidatorGroup;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConsolidatorGroupController extends Controller
{
    private ?User $user;

    public function __construct()
    {
        $this->user = JWTAuth::user();
    }

    /** List all groups for the caller's permitted orgs. */
    public function index(Request $request): JsonResponse
    {
        $orgIds = $this->user->getOrgsByPermission('consolidator-group-index');

        if (empty($orgIds)) {
            return response()->json(['data' => [], 'total' => 0]);
        }

        $query = ConsolidatorGroup::with(['users:id,name,last_name,email'])
            ->whereIn('org_id', $orgIds);

        if ($request->filled('filter')) {
            $term = '%' . $request->filter . '%';
            $query->where('name', 'like', $term);
        }

        if ($request->filled('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        $itemsPerPage = (int) $request->get('itemsPerPage', 10);
        $page         = (int) $request->get('page', 1);

        if ($itemsPerPage === -1) {
            $items = $query->orderBy('name')->get();
            return response()->json(['data' => $items, 'total' => $items->count()]);
        }

        $paginator = $query->orderBy('name')->paginate($itemsPerPage, ['*'], 'page', $page);

        return response()->json([
            'data'  => $paginator->items(),
            'total' => $paginator->total(),
        ]);
    }

    /** Create a new group. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'org_id' => 'required|integer|exists:organizations,id',
        ]);

        $orgIds = $this->user->getOrgsByPermission('consolidator-group-manage');
        if (!in_array((int) $data['org_id'], $orgIds)) {
            abort(403, 'Sin permiso para esta organización.');
        }

        $group = ConsolidatorGroup::create([
            'name'       => $data['name'],
            'org_id'     => $data['org_id'],
            'created_by' => $this->user->id,
        ]);

        $group->load('users:id,name,last_name,email');

        return response()->json(['success' => 'Grupo creado.', 'data' => $group], 201);
    }

    /** Rename a group. */
    public function update(Request $request, int $id): JsonResponse
    {
        $group = $this->findInScope($id);

        $data = $request->validate(['name' => 'required|string|max:255']);
        $group->update(['name' => $data['name']]);
        $group->load('users:id,name,last_name,email');

        return response()->json(['success' => 'Grupo actualizado.', 'data' => $group]);
    }

    /** Delete a group. */
    public function destroy(int $id): JsonResponse
    {
        $this->findInScope($id)->delete();

        return response()->json(['success' => 'Grupo eliminado.']);
    }

    /**
     * Replace the user members of a group.
     * Expects: { user_ids: [1, 2, 3] }
     */
    public function syncMembers(Request $request, int $id): JsonResponse
    {
        $group = $this->findInScope($id);

        $data = $request->validate([
            'user_ids'   => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $group->users()->sync($data['user_ids'] ?? []);
        $group->load('users:id,name,last_name,email');

        return response()->json(['success' => 'Miembros del grupo actualizados.', 'data' => $group]);
    }

    private function findInScope(int $id): ConsolidatorGroup
    {
        $orgIds = $this->user->getOrgsByPermission('consolidator-group-manage');

        return ConsolidatorGroup::whereIn('org_id', $orgIds)->findOrFail($id);
    }
}
