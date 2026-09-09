<?php

declare(strict_types=1);

namespace App\Models\Church;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChurchMemberConsolidatorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_member_id',
        'consolidator_id',
        'action',
        'changed_by',
    ];

    public function churchMember(): BelongsTo
    {
        return $this->belongsTo(ChurchMember::class);
    }

    public function consolidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consolidator_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
