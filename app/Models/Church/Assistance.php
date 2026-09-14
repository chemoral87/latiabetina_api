<?php

declare(strict_types=1);

namespace App\Models\Church;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assistance extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_id',
        'assistance_date',
        'service_time',
        'adults',
        'teens',
        'kids',
        'babies',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assistance_date' => 'date:Y-m-d',
        'adults' => 'integer',
        'teens' => 'integer',
        'kids' => 'integer',
        'babies' => 'integer',
    ];

    protected $appends = ['total'];

    public function getTotalAttribute(): int
    {
        return (int) $this->adults + (int) $this->teens + (int) $this->kids + (int) $this->babies;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
