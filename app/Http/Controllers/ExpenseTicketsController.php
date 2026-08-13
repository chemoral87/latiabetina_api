<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\ExpenseTicket;
use App\Models\Store;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ExpenseTicketsController extends Controller
{
  use AppliesOrgPermissionScope;

  protected $user;

  public function __construct() {
    $this->user = JWTAuth::user();
  }

  private function scopeByOrg($query) {
    $orgIds = $this->user->getOrgsByPermission('expense-ticket-index');
    if (empty($orgIds)) {
      return $query->whereRaw('1 = 0');
    }
    return $query->whereHas('store', function ($q) use ($orgIds) {
      $q->whereIn('org_id', $orgIds);
    });
  }

  private function assertStoreAllowed($storeId) {
    $orgIds = $this->user->getOrgsByPermission('expense-ticket-index');
    $store = Store::find($storeId);
    if ($store == null || !in_array($store->org_id, $orgIds)) {
      abort(403, 'Store not allowed');
    }
  }

  public function index(Request $request) {
    $filter = $request->get("filter");
    $query = queryServerSide($request, ExpenseTicket::query()->with(["images", "store"]));
    $query = $this->scopeByOrg($query);
    if ($filter) {
      $query->where("description", "like", "%" . $filter . "%");
    }
    $tickets = $query->paginate($request->get('itemsPerPage'));
    return new DataSetResource($tickets);
  }

  public function show($id) {
    $ticket = $this->scopeByOrg(ExpenseTicket::with(["images", "store"]))->where("id", $id)->first();
    if ($ticket == null) {
      abort(405, 'Expense ticket not found');
    }
    return response()->json($ticket);
  }

  public function create(Request $request) {
    $userId = JWTAuth::user()->id;

    $request->validate([
      'store_id' => 'required|exists:stores,id',
      'date' => 'required|date',
      'total' => 'required|numeric',
      'description' => 'nullable|string',
    ]);

    $this->assertStoreAllowed($request->get('store_id'));

    $ticket = ExpenseTicket::create([
      'store_id' => $request->get('store_id'),
      'date' => $request->get('date'),
      'total' => $request->get('total'),
      'description' => $request->get('description'),
      'created_by' => $userId,
      'updated_by' => $userId,
    ]);

    return ['success' => 'Expense ticket created', 'data' => $ticket->load(['images', 'store'])];
  }

  public function update(Request $request, $id) {
    $userId = JWTAuth::user()->id;
    $ticket = $this->scopeByOrg(ExpenseTicket::query())->where("id", $id)->first();
    if ($ticket == null) {
      abort(405, 'Expense ticket not found');
    }

    $request->validate([
      'store_id' => 'sometimes|exists:stores,id',
      'date' => 'sometimes|date',
      'total' => 'sometimes|numeric',
      'description' => 'nullable|string',
    ]);

    if ($request->has('store_id')) {
      $this->assertStoreAllowed($request->get('store_id'));
    }

    $ticket->store_id = $request->get('store_id', $ticket->store_id);
    $ticket->date = $request->get('date', $ticket->date);
    $ticket->total = $request->get('total', $ticket->total);
    $ticket->description = $request->get('description', $ticket->description);
    $ticket->updated_by = $userId;
    $ticket->save();

    return ['success' => 'Expense ticket updated', 'data' => $ticket->load(['images', 'store'])];
  }

  public function delete($id) {
    $ticket = $this->scopeByOrg(ExpenseTicket::query())->where("id", $id)->first();
    if ($ticket == null) {
      abort(405, 'Expense ticket not found');
    }
    $ticket->delete();
    return ['success' => 'Expense ticket deleted'];
  }

}
