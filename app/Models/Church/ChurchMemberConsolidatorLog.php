<?php

namespace App\Models\Church;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurchMemberConsolidatorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_member_id',
        'consolidator_id',
        'action',
        'changed_by',
    ];

    public function churchMember()
    {
        return $this->belongsTo(ChurchMember::class);
    }

    public function consolidator()
    {
        return $this->belongsTo(User::class, 'consolidator_id');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
