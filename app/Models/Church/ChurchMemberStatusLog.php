<?php

namespace App\Models\Church;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurchMemberStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_member_id',
        'new_status',
        'reason',
        'changed_by',
    ];

    public function churchMember()
    {
        return $this->belongsTo(ChurchMember::class);
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}