<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'purchaseInvoice';
    protected $primaryKey = 'id';
    protected string $key = 'string';

    protected $fillable = [
        'date',
        'totalAmount',
        'totalTax',
        'paidAmount',
        'dueAmount',
        'supplierId',
        'note',
        'supplierMemoNo',
        'invoiceMemoNo',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = self::generateUniqueKey(13);
        });
    }

    /**
     * @throws Exception
     */
    protected static function generateUniqueKey($length): string
    {
        $characters = "ABCDEFGHOPQRSTUYZ0123456IJKLMN789VWX";
        $key = "P_";

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }
        // Ensure the key is unique
        while (static::where('id', $key)->exists()) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplierId');
    }

    public function returnPurchaseInvoice(): HasMany
    {
        return $this->hasMany(ReturnPurchaseInvoice::class, 'purchaseInvoiceId');
    }

    public function purchaseInvoiceProduct(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceProduct::class, 'invoiceId');
    }
}
