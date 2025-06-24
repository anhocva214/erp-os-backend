<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeightUnit extends Model
{
    use HasFactory;

    protected $table = 'weightUnit';

    protected $fillable = [
        'name'
    ];
}
