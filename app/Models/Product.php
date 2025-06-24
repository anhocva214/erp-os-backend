<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'productThumbnailImage',
        'productSubCategoryId',
        'productBrandId',
        'description',
        'sku',
        'productQuantity',
        'productSalePrice',
        'productPurchasePrice',
        'uomId',
        'uomValue',
        'reorderQuantity',
        'productVatId',
        'discountId',
        'productPurchaseVatId'
    ];

    public function productSubCategory(): BelongsTo
    {
        return $this->belongsTo(ProductSubCategory::class, 'productSubCategoryId');
    }

    public function productBrand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'productBrandId');
    }

    public function productColor(): HasMany
    {
        return $this->hasMany(ProductColor::class, 'productId');
    }
    
    public function productProductAttributeValue(): HasMany
    {
        return $this->hasMany(ProductProductAttributeValue::class, 'productId');
    }

    public function galleryImage(): HasMany
    {
        return $this->hasMany(Images::class, 'productId');
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'discountId');
    }

    
    public function productVat(): BelongsTo
    {
        return $this->belongsTo(ProductVat::class, 'productVatId');
    }

    public function productPurchaseVat(): BelongsTo
    {
        return $this->belongsTo(ProductVat::class, 'productPurchaseVatId');
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(UoM::class, 'uomId');
    }
}
