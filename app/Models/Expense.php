<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'concept_id',
        'ticket_id',
        'unit',
        'quantity',
        'amount',
        'total',
        'date',
        'created_by',
        'updated_by',
    ];

    public function concept() {
        return $this->belongsTo(ExpenseConcept::class);
    }

    public function ticket() {
        return $this->belongsTo(ExpenseTicket::class);
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
