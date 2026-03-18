<?php

namespace App\Http\Controllers;

use App\Models\ChurchMember;
use Illuminate\Http\Request;

class ChurchMemberController extends Controller
{
    public function index(Request $request)
    {
        $query = ChurchMember::query();

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

        return response()->json($query->get());
    }

    public function show($id)
    {
        $member = ChurchMember::findOrFail($id);
        return response()->json($member);
    }

    public function create(Request $request)
    {
        $request->validate([
            'conso_sheet_id' => 'required|exists:conso_sheets,id',
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
}
