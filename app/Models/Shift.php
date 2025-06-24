<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\hasMany;

class Shift extends Model
{
    use HasFactory;

    //create shift model
    protected $table = 'shift';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'startTime',
        'endTime',
        'workHour',
    ];

    public function user(): hasMany
    {
        return $this->hasMany(Users::class, "shiftId");
    }
}
