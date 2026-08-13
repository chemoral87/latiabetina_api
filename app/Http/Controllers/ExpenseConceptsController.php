<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\ExpenseConcept;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ExpenseConceptsController extends Controller
{
  public function index(Request $request) {
    $filter = $request->get("filter");
    $query = queryServerSide($request, ExpenseConcept::query());
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }
    $concepts = $query->with(["categories"])->paginate($request->get('itemsPerPage'));
    return new DataSetResource($concepts);
  }

  public function show($id) {
    $concept = ExpenseConcept::with(["categories"])->where("id", $id)->first();
    if ($concept == null) {
      abort(405, 'Expense concept not found');
    }
    return response()->json($concept);
  }

  public function create(Request $request) {
    $userId = JWTAuth::user()->id;

    $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
    ]);

    $concept = ExpenseConcept::create([
      'name' => $request->get('name'),
      'description' => $request->get('description'),
      'created_by' => $userId,
      'updated_by' => $userId,
    ]);

    return ['success' => 'Expense concept created', 'data' => $concept];
  }

  public function update(Request $request, $id) {
    $userId = JWTAuth::user()->id;
    $concept = ExpenseConcept::find($id);
    if ($concept == null) {
      abort(405, 'Expense concept not found');
    }

    $request->validate([
      'name' => 'sometimes|string|max:255',
      'description' => 'nullable|string',
    ]);

    $concept->name = $request->get('name', $concept->name);
    $concept->description = $request->get('description', $concept->description);
    $concept->updated_by = $userId;
    $concept->save();

    return ['success' => 'Expense concept updated', 'data' => $concept];
  }

  public function delete($id) {
    $concept = ExpenseConcept::find($id);
    if ($concept == null) {
      abort(405, 'Expense concept not found');
    }
    $concept->categories()->detach();
    $concept->delete();
    return ['success' => 'Expense concept deleted'];
  }

}
