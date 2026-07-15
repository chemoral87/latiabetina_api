<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
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

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getImageS3Attribute()
    {
        return permanentUrlS3($this->image);
    }
}
