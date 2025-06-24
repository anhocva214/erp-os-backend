<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\hasMany;

class Department extends Model
{
    use HasFactory;

    //create department model
    protected $table = 'department';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
    ];

    public function user(): HasMany
    {
        return $this->hasMany(Users::class, 'departmentId');
    }
}
