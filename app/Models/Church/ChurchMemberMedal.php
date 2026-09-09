<?php

declare(strict_types=1);

namespace App\Models\Church;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchMemberMedal extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_member_id',
        'medal',
        'description',
        'created_by',
    ];

    public function churchMember(): BelongsTo
    {
        return $this->belongsTo(ChurchMember::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
