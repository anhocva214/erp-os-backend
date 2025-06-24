<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    use HasFactory;
    protected $table = 'productAttributeValue';
    protected $primaryKey = 'id';
    protected $fillable = [
        'productAttributeId',
        'name',
    ];

    public function productAttribute()
    {
        return $this->belongsTo(ProductAttribute::class, 'productAttributeId');
    }

    public function productProductAttributeValue()
    {
        return $this->hasMany(ProductProductAttributeValue::class, 'productAttributeValueId');
    }
}
