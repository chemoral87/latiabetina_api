<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseTicket extends Model {
  use HasFactory;

  protected $fillable = [
    'store_id',
    'date',
    'total',
    'description',
    'created_by',
    'updated_by',
  ];

  public function images() {
    return $this->hasMany(ExpenseTicketImage::class);
  }
}
