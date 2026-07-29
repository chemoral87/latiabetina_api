<?php

namespace App\Models\LifeGroup;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'life_group_people';

    protected $fillable = [
        'life_group_id',
        'name',
        'last_name',
        'age',
        'gender',
        'phone',
        'photo',
    ];

    public function lifeGroup()
    {
        return $this->belongsTo(LifeGroup::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function sessions()
    {
        return $this->belongsToMany(Session::class, 'life_group_attendances')
            ->withPivot(['type', 'observations'])
            ->withTimestamps();
    }
}
