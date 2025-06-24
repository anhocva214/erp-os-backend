<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachment';
    protected $primaryKey = 'id';

    protected $fillable = [
        'emailId',
        'name',
    ];

    public function email()
    {
        return $this->belongsTo(Email::class, 'emailId');
    }
}

