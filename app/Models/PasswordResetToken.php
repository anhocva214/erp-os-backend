<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasFactory;

    protected $table = 'PasswordResetToken';
    protected $primaryKey = 'id';
    protected $fillable = [
        'userId',
        'token',
        'experiresAt',
    ];
}
