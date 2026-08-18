<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurchMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_id',
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

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function consoSheet()
    {
        return $this->belongsTo(ConsoSheet::class);
    }

    public function consolidators()
    {
        return $this->belongsToMany(User::class, 'church_member_consolidator', 'church_member_id', 'consolidator_id')->withTimestamps();
    }
}
