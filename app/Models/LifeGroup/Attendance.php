<?php

namespace App\Models\LifeGroup;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $table = 'life_group_attendances';

    protected $fillable = [
        'session_id',
        'person_id',
        'type',
        'observations',
    ];

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
