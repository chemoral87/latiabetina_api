<?php

declare(strict_types=1);

namespace App\Models\LifeGroup;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LifeGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'life_groups';

    protected $fillable = [
        'name',
        'address',
        'reference',
        'neighborhood',
        'latitude',
        'longitude',
        'start_date',
        'time',
        'day_of_week',
        'observations',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'time' => 'string',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'life_group_leaders', 'life_group_id', 'user_id')
            ->withTimestamps();
    }
}
