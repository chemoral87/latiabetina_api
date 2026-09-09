<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\ExpenseTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExpenseTicketImage extends Model {
  use HasFactory;

  protected $fillable = [
    'expense_ticket_id',
    'image_path',
    'description',
  ];

  public function ticket(): BelongsTo
    {
        return $this->belongsTo(ExpenseTicket::class);
    }
}
