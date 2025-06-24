<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductProductAttributeValue extends Model
{
    use HasFactory;

    protected $table = 'productProductAttributeValue';
    protected $primaryKey = 'id';
    protected $fillable = [
        'productId',
        'productAttributeValueId',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productId');
    }

    public function productAttributeValue(): BelongsTo
    {
        return $this->belongsTo(ProductAttributeValue::class, 'productAttributeValueId');
    }
}
