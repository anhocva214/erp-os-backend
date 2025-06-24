<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturnSaleInvoice extends Model
{
    use HasFactory;
    use HasUuids;

    protected $table = 'returnSaleInvoice';
    protected $primaryKey = 'id';
    protected string $key = 'string';

    protected $fillable = [
        'date',
        'totalAmount',
        'note',
        'saleInvoiceId',
        'invoiceMemoNo',
        'instantReturnAmount',
        'tax'
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
        $key = "SR_";

        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }
        // Ensure the key is unique
        while (static::where('id', $key)->exists()) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $key;
    }

    public function saleInvoice(): BelongsTo
    {
        return $this->belongsTo(SaleInvoice::class, 'saleInvoiceId');
    }

    public function returnSaleInvoiceProduct(): HasMany
    {
        return $this->hasMany(ReturnSaleInvoiceProduct::class, 'invoiceId');
    }
}
