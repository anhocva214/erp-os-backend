<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualPayment extends Model
{
    use HasFactory;

    protected $table = 'manualPayment';
    protected $primaryKey = 'id';
    protected $fillable = [
        'paymentMethodId',
        'customerId',
        'amount',
        'manualTransactionId',
        'CustomerAccount',
        'CustomerTransactionId',
        'isVerified',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customerId', 'id');
    }

   

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'paymentMethodId', 'id');
    }

}