<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnPurchaseInvoiceProduct extends Model
{
    use HasFactory;
    protected $table = 'returnPurchaseInvoiceProduct';
    protected $primaryKey = 'id';
    protected $fillable = [
        'invoiceId',
        'purchaseInvoiceProductId',
        'productId',
        'productQuantity',
        'productUnitPurchasePrice',
        'productFinalAmount',
        'tax',
        'taxAmount',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(ReturnPurchaseInvoice::class, 'invoiceId');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'productId');
    }
}
