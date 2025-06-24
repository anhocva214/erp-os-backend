<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    use HasFactory;
    protected $table = 'productAttribute';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
    ];

    public function productAttributeValue()
    {
        return $this->hasMany(ProductAttributeValue::class, 'productAttributeId');
    }
}
