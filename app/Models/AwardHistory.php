<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AwardHistory extends Model
{
    use HasFactory;

    protected $table = 'awardHistory';
    protected $primaryKey = 'id';

    protected $fillable = [
        'userId',
        'awardId',
        'awardedDate',
        'comment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Users::class, 'userId');
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class, 'awardId');
    }
}
