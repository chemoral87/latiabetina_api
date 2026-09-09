<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ExpenseTicket extends Model {
  use HasFactory;

  protected $fillable = [
    'store_id',
    'date',
    'total',
    'description',
    'created_by',
    'updated_by',
  ];

  public function images(): HasMany {
    return $this->hasMany(ExpenseTicketImage::class);
  }

  public function store(): BelongsTo {
    return $this->belongsTo(Store::class);
  }
}
