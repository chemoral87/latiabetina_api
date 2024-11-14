<?php

namespace App\Models;

use App\Models\ExpenseTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseTicketImage extends Model {
  use HasFactory;

  protected $fillable = [
    'expense_ticket_id',
    'image_path',
    'description',
  ];

  public function ticket()
    {
        return $this->belongsTo(ExpenseTicket::class);
    }
}
