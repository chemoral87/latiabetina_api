<?php

declare(strict_types=1);

namespace App\Models\Church;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchMemberTrackingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_member_id',
        'contact_datetime',
        'medium',
        'classification',
        'description',
        'created_by',
    ];

    protected $casts = [
        'contact_datetime' => 'datetime',
    ];

    public function churchMember(): BelongsTo
    {
        return $this->belongsTo(ChurchMember::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getChurchMemberNameAttribute(): ?string
    {
        return $this->churchMember ? $this->churchMember->name . ' ' . $this->churchMember->last_name : null;
    }
}