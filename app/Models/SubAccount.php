<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubAccount extends Model
{
    use HasFactory;

    protected $table = 'subAccount';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'accountId',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accountId');
    }

    public function debit(): HasMany
    {
        return $this->hasMany(Transaction::class, 'debitId');
    }

    public function credit(): HasMany
    {
        return $this->hasMany(Transaction::class, 'creditId');
    }

    public function paymentMethod(): HasMany
    {
        return $this->hasMany(PaymentMethod::class, 'subAccountId');
    }
}
