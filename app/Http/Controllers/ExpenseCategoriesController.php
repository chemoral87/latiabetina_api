<?php

namespace App\Http\Controllers;

use App\Http\Resources\DataSetResource;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ExpenseCategoriesController extends Controller
{
  public function index(Request $request) {
    $filter = $request->get("filter");
    $query = queryServerSide($request, ExpenseCategory::query());
    if ($filter) {
      $query->where("name", "like", "%" . $filter . "%");
    }
    $categories = $query->with(["concepts"])->paginate($request->get('itemsPerPage'));
    return new DataSetResource($categories);
  }

  public function show($id) {
    $category = ExpenseCategory::with(["concepts"])->where("id", $id)->first();
    if ($category == null) {
      abort(405, 'Expense category not found');
    }
    return response()->json($category);
  }

  public function create(Request $request) {
    $userId = JWTAuth::user()->id;

    $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
      'logo' => 'nullable|string',
      'color' => 'nullable|string',
      'should_display' => 'nullable|string',
      'order' => 'nullable|string',
      'concepts' => 'nullable|array',
      'concepts.*' => 'exists:expense_concepts,id',
    ]);

    $category = ExpenseCategory::create([
      'name' => $request->get('name'),
      'description' => $request->get('description'),
      'logo' => $request->get('logo'),
      'color' => $request->get('color'),
      'should_display' => $request->get('should_display'),
      'order' => $request->get('order'),
      'created_by' => $userId,
      'updated_by' => $userId,
    ]);

    $category->concepts()->sync($request->get('concepts', []));

    return ['success' => 'Expense category created', 'data' => $category->load('concepts')];
  }

  public function update(Request $request, $id) {
    $userId = JWTAuth::user()->id;
    $category = ExpenseCategory::find($id);
    if ($category == null) {
      abort(405, 'Expense category not found');
    }

    $request->validate([
      'name' => 'sometimes|string|max:255',
      'description' => 'nullable|string',
      'logo' => 'nullable|string',
      'color' => 'nullable|string',
      'should_display' => 'nullable|string',
      'order' => 'nullable|string',
      'concepts' => 'nullable|array',
      'concepts.*' => 'exists:expense_concepts,id',
    ]);

    $category->name = $request->get('name', $category->name);
    $category->description = $request->get('description', $category->description);
    $category->logo = $request->get('logo', $category->logo);
    $category->color = $request->get('color', $category->color);
    $category->should_display = $request->get('should_display', $category->should_display);
    $category->order = $request->get('order', $category->order);
    $category->updated_by = $userId;
    $category->save();

    if ($request->has('concepts')) {
      $category->concepts()->sync($request->get('concepts', []));
    }

    return ['success' => 'Expense category updated', 'data' => $category->load('concepts')];
  }

  public function delete($id) {
    $category = ExpenseCategory::find($id);
    if ($category == null) {
      abort(405, 'Expense category not found');
    }
    $category->concepts()->detach();
    $category->delete();
    return ['success' => 'Expense category deleted'];
  }

}
