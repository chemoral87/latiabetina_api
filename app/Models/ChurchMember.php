<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurchMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'conso_sheet_id',
        'name',
        'last_name',
        'second_last_name',
        'years_old',
        'number_of_children',
        'cellphone',
        'address',
        'marriage_status',
    ];

    public function consoSheet()
    {
        return $this->belongsTo(ConsoSheet::class);
    }
}
