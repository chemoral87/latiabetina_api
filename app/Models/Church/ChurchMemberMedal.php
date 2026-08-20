<?php

namespace App\Models\Church;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChurchMemberMedal extends Model
{
    use HasFactory;

    protected $fillable = [
        'church_member_id',
        'medal',
        'description',
        'created_by',
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