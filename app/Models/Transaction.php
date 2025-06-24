<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $table = 'transaction';
    protected $primaryKey = 'id';

    protected $fillable = [
        'date',
        'debitId',
        'creditId',
        'particulars',
        'amount',
        'type',
        'relatedId',
        'status',
    ];

    public function debit(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class, 'debitId');
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(SubAccount::class, 'creditId');
    }
}
