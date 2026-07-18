<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'pos_sale_items';

    /**
     * Allowed values for preparation_status:
     *   null      – product does not require preparation
     *   PEN       – item is queued in the KDS, waiting to be made
     *   REA       – kitchen has marked this item as ready
     *   COM       – customer has received/accepted this item
     */
    const PREPARATION_PENDING   = 'PEN';
    const PREPARATION_READY     = 'REA';
    const PREPARATION_COMPLETED = 'COM';

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_price',
        'preparation_status',
        'completed_quantity',
    ];

    protected $casts = [
        'quantity'           => 'integer',
        'unit_price'         => 'decimal:2',
        'total_price'        => 'decimal:2',
        'preparation_status' => 'string',
        'completed_quantity' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /** True when this item is sitting in the KDS queue. */
    public function isPending(): bool
    {
        return $this->preparation_status === self::PREPARATION_PENDING;
    }

    /** True when the kitchen has finished this item. */
    public function isReady(): bool
    {
        return $this->preparation_status === self::PREPARATION_READY;
    }

    /** True when the customer has received this item. */
    public function isCompleted(): bool
    {
        return $this->preparation_status === self::PREPARATION_COMPLETED;
    }

    public function getPreparationStatusLabel(): string
    {
        return match ($this->preparation_status) {
            self::PREPARATION_PENDING => 'Pendiente',
            self::PREPARATION_READY => 'Listo',
            self::PREPARATION_COMPLETED => 'Completada',
            default => '—',
        };
    }

    /** Mark item as ready (kitchen finished). */
    public function markReady(): self
    {
        $this->update(['preparation_status' => self::PREPARATION_READY]);
        return $this;
    }

    /** Mark item as completed (customer received). */
    public function markCompleted(): self
    {
        $this->update(['preparation_status' => self::PREPARATION_COMPLETED]);
        return $this;
    }
}
