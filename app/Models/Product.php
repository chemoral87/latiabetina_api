<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Product extends Model
{
    use SoftDeletes;

    protected $table = 'pos_products';

    protected $fillable = [
        'org_id',
        'name',
        'sku',
        'description',
        'image',
        'hidden',
        'requires_preparation',
        'price',
        'stock',
        'order',
        'created_by',
        'updated_by',
    ];

    protected $hidden = ['image'];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'order' => 'integer',
        'hidden' => 'boolean',
        'requires_preparation' => 'boolean',
    ];

    protected $appends = ['image_s3'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getImageS3Attribute(): ?string
    {
        return permanentUrlS3($this->image);
    }
}
