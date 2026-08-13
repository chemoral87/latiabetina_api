<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesOrgPermissionScope;
use App\Http\Resources\DataSetResource;
use App\Models\Expense;
use App\Models\ExpenseTicket;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ExpensesController extends Controller
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
    return $query->whereHas('ticket.store', function ($q) use ($orgIds) {
      $q->whereIn('org_id', $orgIds);
    });
  }

  private function assertTicketAllowed($ticketId) {
    $orgIds = $this->user->getOrgsByPermission('expense-ticket-index');
    $ticket = ExpenseTicket::with('store')->find($ticketId);
    if ($ticket == null || $ticket->store == null || !in_array($ticket->store->org_id, $orgIds)) {
      abort(403, 'Expense ticket not allowed');
    }
  }

  public function index(Request $request) {
    $query = queryServerSide($request, Expense::query()->with(["concept", "ticket"]));
    $query = $this->scopeByOrg($query);

    if ($request->has('ticket_id') && !empty($request->ticket_id)) {
      $query->where('ticket_id', $request->ticket_id);
    }
    if ($request->has('concept_id') && !empty($request->concept_id)) {
      $query->where('concept_id', $request->concept_id);
    }
    if ($request->has('date_from') && !empty($request->date_from)) {
      $query->where('date', '>=', $request->date_from);
    }
    if ($request->has('date_to') && !empty($request->date_to)) {
      $query->where('date', '<=', $request->date_to);
    }

    $expenses = $query->paginate($request->get('itemsPerPage'));
    return new DataSetResource($expenses);
  }

  public function show($id) {
    $expense = $this->scopeByOrg(Expense::with(["concept", "ticket"]))->where("id", $id)->first();
    if ($expense == null) {
      abort(405, 'Expense not found');
    }
    return response()->json($expense);
  }

  public function create(Request $request) {
    $userId = JWTAuth::user()->id;

    $request->validate([
      'concept_id' => 'required|exists:expense_concepts,id',
      'ticket_id' => 'required|exists:expense_tickets,id',
      'unit' => 'required|string',
      'quantity' => 'required|numeric',
      'amount' => 'required|numeric',
      'total' => 'required|numeric',
      'date' => 'required|date',
    ]);

    $this->assertTicketAllowed($request->get('ticket_id'));

    $expense = Expense::create([
      'concept_id' => $request->get('concept_id'),
      'ticket_id' => $request->get('ticket_id'),
      'unit' => $request->get('unit'),
      'quantity' => $request->get('quantity'),
      'amount' => $request->get('amount'),
      'total' => $request->get('total'),
      'date' => $request->get('date'),
      'created_by' => $userId,
      'updated_by' => $userId,
    ]);

    return ['success' => 'Expense created', 'data' => $expense->load(['concept', 'ticket'])];
  }

  public function update(Request $request, $id) {
    $userId = JWTAuth::user()->id;
    $expense = $this->scopeByOrg(Expense::query())->where("id", $id)->first();
    if ($expense == null) {
      abort(405, 'Expense not found');
    }

    $request->validate([
      'concept_id' => 'sometimes|exists:expense_concepts,id',
      'ticket_id' => 'sometimes|exists:expense_tickets,id',
      'unit' => 'sometimes|string',
      'quantity' => 'sometimes|numeric',
      'amount' => 'sometimes|numeric',
      'total' => 'sometimes|numeric',
      'date' => 'sometimes|date',
    ]);

    if ($request->has('ticket_id')) {
      $this->assertTicketAllowed($request->get('ticket_id'));
    }

    $expense->concept_id = $request->get('concept_id', $expense->concept_id);
    $expense->ticket_id = $request->get('ticket_id', $expense->ticket_id);
    $expense->unit = $request->get('unit', $expense->unit);
    $expense->quantity = $request->get('quantity', $expense->quantity);
    $expense->amount = $request->get('amount', $expense->amount);
    $expense->total = $request->get('total', $expense->total);
    $expense->date = $request->get('date', $expense->date);
    $expense->updated_by = $userId;
    $expense->save();

    return ['success' => 'Expense updated', 'data' => $expense->load(['concept', 'ticket'])];
  }

  public function delete($id) {
    $expense = $this->scopeByOrg(Expense::query())->where("id", $id)->first();
    if ($expense == null) {
      abort(405, 'Expense not found');
    }
    $expense->delete();
    return ['success' => 'Expense deleted'];
  }

}
