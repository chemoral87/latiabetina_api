<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SaleEventLog extends Model
{
    protected $table = 'sale_event_logs';

    protected $fillable = [
        'sale_id',
        'number',
        'org_id',
        'customer_name',
        'has_preparation_items',
        'broadcast_data',
    ];

    protected $casts = [
        'sale_id' => 'integer',
        'org_id' => 'integer',
        'has_preparation_items' => 'boolean',
        'broadcast_data' => 'json',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
