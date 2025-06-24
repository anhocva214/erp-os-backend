<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnPurchaseInvoice extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'returnPurchaseInvoice';
    protected $primaryKey = 'id';
    protected string $key = 'string';

    protected $fillable = [
        'date',
        'totalAmount',
        'instantReturnAmount',
        'tax',
        'note',
        'purchaseInvoiceId',
        'invoiceMemoNo',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = self::generateUniqueKey(12);
        });
    }

    /**
     * @throws Exception
     */
    protected static function generateUniqueKey($length): string
    {
        $characters = "ABCDEFGHOPQRSTUYZ0123456IJKLMN789VWX";
        $key = "PR_";

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }
        // Ensure the key is unique
        while (static::where('id', $key)->exists()) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchaseInvoiceId');
    }

    public function returnPurchaseInvoiceProduct(): HasMany
    {
        return $this->hasMany(ReturnPurchaseInvoiceProduct::class, 'invoiceId');
    }
}
