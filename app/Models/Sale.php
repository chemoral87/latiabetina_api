<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Sale extends Model
{
    protected $table = 'pos_sales';

    protected $fillable = [
        'number',
        'org_id',
        'customer_name',
        'customer_phone',
        'payment_method',
        'subtotal',
        'discount',
        'total',
        'status',
        'notes',
        'created_by',
        'sold_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'sold_at' => 'datetime',
    ];

    public const STATUS_PENDING   = 'PEN';
    public const STATUS_PREPARING = 'PRE';
    public const STATUS_COMPLETED = 'COM';
    public const STATUS_CANCELLED = 'CAN';
    public const STATUS_REFUNDED  = 'REF';

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
