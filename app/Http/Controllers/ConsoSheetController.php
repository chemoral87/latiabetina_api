<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Controllers\Concerns\AppliesSort;
use App\Http\Resources\DataSetResource;
use App\Models\ConsoSheet;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;

class ConsoSheetController extends Controller
{
    use AppliesOrgPermissionScope;
    use AppliesSort;

      protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }
    public function index(Request $request)
    {
        $query = ConsoSheet::with(['creator', 'organization']);

        // Si tiene conso-sheet-all puede ver todos los orgs (con restricción a orgs permitidos)
        $hasAll = false;
        try {
            $hasAll = method_exists($this->user, 'hasPermissionTo') && $this->user->hasPermissionTo('conso-sheet-all');
        } catch (\Throwable $e) {
            $hasAll = false;
        }
        if (!$hasAll) {
            $hasAll = !empty($this->user->getOrgsByPermission('conso-sheet-all'));
        }

        if ($hasAll) {
            // For conso-sheet-all, apply org scope based on permitted orgs
            $orgIds = $this->user->getOrgsByPermission('conso-sheet-all');
            if (!empty($orgIds)) {
                $query->whereIn('org_id', $orgIds);
            } else {
                // No organizations with this permission - return no results
                $query->whereRaw('1 = 0');
            }
        } else {
            $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');

            $query->where(function ($q) {
                $q->where('created_by', $this->user->id)
                    ->orWhereHas('churchMembers.consolidators', function ($q) {
                        $q->where('users.id', $this->user->id);
                    });
            });
        }

        if ($request->has('filter') && !empty($request->filter)) {
            $query->where('folio_number', 'like', '%' . $request->filter . '%');
        }

        $query = $this->applyOrgIdFilter($query, $request);

        $itemsPerPage = $request->get('itemsPerPage', 10);
        $sortBy = $request->get('sortBy', ['id']);
        $sortDesc = $request->get('sortDesc', [true]);

        if (!empty($sortBy) && is_array($sortBy)) {
            $query = $this->applySort($query, $sortBy, $sortDesc);
        }

        if ($itemsPerPage === '-1') {
            return response()->json(['data' => $query->get()]);
        }

        return new DataSetResource($query->paginate($itemsPerPage));
    }

    public function consolidators(Request $request)
    {
        $orgId = $request->integer('org_id');
        $queryText = trim((string) $request->get('queryText', ''));
        $ids = $request->get('ids', []);

        $users = \App\Models\User::query()
            ->select('id', 'name', 'last_name', 'second_last_name', 'email')
            ->whereNotIn('id', $ids)
            ->whereHas('profiles', function ($query) use ($orgId) {
                $query->where('org_id', $orgId)->where(function ($q) {
                    $q->whereHas('roles.permissions', function ($p) {
                        $p->where('name', 'conso-sheet-index');
                    })->orWhereHas('permissions', function ($p) {
                        $p->where('name', 'conso-sheet-index');
                    });
                });
            });

        if ($queryText !== '') {
            $users->where(function ($q) use ($queryText) {
                $q->where(\Illuminate\Support\Facades\DB::raw("CONCAT_WS(' ', name, last_name, second_last_name)"), 'like', '%' . $queryText . '%')
                    ->orWhere('email', 'like', '%' . $queryText . '%');
            });
        }

        return response()->json($users->orderBy('name')->paginate(7)->items());
    }

    public function show($id)
    {
        $sheet = ConsoSheet::with(['creator', 'organization'])->findOrFail($id);
        return response()->json($sheet);
    }

    public function create(Request $request)
    {
        $request->validate([
            'org_id'       => 'required|exists:organizations,id',
            'folio_number' => 'required|string|unique:conso_sheets',
            'date'         => 'required|date',
            'how_did_you_hear'           => 'nullable|string|max:255',
            'first_time_christian_church' => 'nullable|boolean',
            'comments'       => 'nullable|string',
            'special_request' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['created_by'] = $this->user->id;

        $sheet = ConsoSheet::with(['creator', 'organization'])->findOrFail(ConsoSheet::create($data)->id);
        return response()->json($sheet, 201);
    }

    public function update(Request $request, $id)
    {
        $sheet = ConsoSheet::findOrFail($id);

        $request->validate([
            'org_id'       => 'sometimes|exists:organizations,id',
            'folio_number' => 'required|string|unique:conso_sheets,folio_number,' . $id,
            'date'         => 'required|date',
            'how_did_you_hear'           => 'nullable|string|max:255',
            'first_time_christian_church' => 'nullable|boolean',
            'comments'       => 'nullable|string',
            'special_request' => 'nullable|string',
        ]);

        $sheet->update($request->all());
        return response()->json($sheet->load(['creator', 'organization']));
    }

    public function delete($id)
    {
        $sheet = ConsoSheet::find($id);
        if ($sheet) {
            $sheet->delete();
        }
        return response()->json(['success' => __('messa.conso_sheet_delete')]);
    }
}
