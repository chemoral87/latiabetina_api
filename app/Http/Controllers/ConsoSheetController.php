<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Models\ConsoSheet;
use Illuminate\Http\Request;

use Tymon\JWTAuth\Facades\JWTAuth;

class ConsoSheetController extends Controller
{
    use AppliesOrgPermissionScope;

      protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }
    public function index(Request $request)
    {
        $query = ConsoSheet::with(['creator', 'organization']);
        $query = $this->applyOrgPermissionScope($query, $this->user, 'conso-sheet-index');

        if ($request->has('filter') && !empty($request->filter)) {
            $query->where('folio_number', 'like', '%' . $request->filter . '%');
        }

        if ($request->has('org_id') && !empty($request->org_id)) {
            $query->where('org_id', $request->org_id);
        }

        $itemsPerPage = $request->get('itemsPerPage', 10);
        $sortBy = $request->get('sortBy', ['id']);
        $sortDesc = $request->get('sortDesc', [true]);

        if (!empty($sortBy) && is_array($sortBy)) {
            foreach ($sortBy as $index => $field) {
                $dir = (isset($sortDesc[$index]) && filter_var($sortDesc[$index], FILTER_VALIDATE_BOOLEAN)) ? 'desc' : 'asc';
                $query->orderBy($field, $dir);
            }
        }

        if ($itemsPerPage === '-1') {
            return response()->json(['data' => $query->get()]);
        }

        return response()->json($query->paginate($itemsPerPage));
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
            'consolidator_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->all();
        $data['created_by'] = $this->user->id;

        $sheet = ConsoSheet::create($data);
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
            'consolidator_id' => 'nullable|exists:users,id',
        ]);

        $sheet->update($request->all());
        return response()->json($sheet);
    }

    public function delete($id)
    {
        $sheet = ConsoSheet::findOrFail($id);
        $sheet->delete();
        return response()->json(['message' => 'Consolidated sheet deleted successfully']);
    }
}
