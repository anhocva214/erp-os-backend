<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Education extends Model
{
    use HasFactory;

    protected $table = 'education';
    protected $primaryKey = 'id';
    protected $fillable = [
        'userId',
        'degree',
        'institution',
        'fieldOfStudy',
        'result',
        'studyStartDate',
        'studyEndDate',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Users::class, 'userId');
    }
}