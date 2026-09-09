<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Church\ChurchMember;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ConsoSheet extends Model
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

    public function churchMembers(): HasMany
    {
        return $this->hasMany(ChurchMember::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Organization::class, 'org_id');
    }
}
