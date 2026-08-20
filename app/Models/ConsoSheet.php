<?php

namespace App\Models;

use App\Models\Church\ChurchMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsoSheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_id',
        'folio_number',
        'date',
        'how_did_you_hear',
        'first_time_christian_church',
        'comments',
        'special_request',
        'created_by',
    ];

    protected $casts = [
        'first_time_christian_church' => 'boolean',
    ];

    public function churchMembers()
    {
        return $this->hasMany(ChurchMember::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id');
    }
}
