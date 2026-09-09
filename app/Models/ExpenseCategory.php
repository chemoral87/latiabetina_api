<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class ExpenseCategory extends Model {
  use HasFactory;

  public function concepts(): BelongsToMany {
    return $this->belongsToMany(ExpenseConcept::class, 'expense_category_concept');
  }
}
