<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Award extends Model
{
    use HasFactory;

    protected $table = 'award';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'description',
        'image',
    ];

    public function awardHistory(): HasMany
    {
        return $this->hasMany(AwardHistory::class, 'awardId');
    }
}
