<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurchMemberTrackingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_member_id',
        'contact_date',
        'medium',
        'description',
        'created_by',
    ];

    protected $casts = [
        'contact_date' => 'date:Y-m-d',
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