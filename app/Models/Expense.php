<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Expense extends Model
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

    public function concept(): BelongsTo {
        return $this->belongsTo(ExpenseConcept::class);
    }

    public function ticket(): BelongsTo {
        return $this->belongsTo(ExpenseTicket::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}
