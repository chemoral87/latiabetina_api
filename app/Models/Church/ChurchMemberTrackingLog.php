<?php

namespace App\Models\Church;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function churchMember()
    {
        return $this->belongsTo(ChurchMember::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}