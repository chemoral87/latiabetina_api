<?php

declare(strict_types=1);

namespace App\Models\LifeGroup;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Session extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'life_group_sessions';

    protected $fillable = [
        'life_group_id',
        'week_number',
        'date',
        'start_time',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'start_time' => 'string',
        ];
    }

    public function lifeGroup(): BelongsTo
    {
        return $this->belongsTo(LifeGroup::class);
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function attendees(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'life_group_attendances')
            ->withPivot(['type', 'observations'])
            ->withTimestamps();
    }
}
