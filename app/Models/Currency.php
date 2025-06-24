<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    public $table = 'currency';

    protected $fillable = [
        'currencyName',
        'currencySymbol',
    ];

    public function appSetting()
    {
        return $this->hasOne(AppSetting::class, 'currencyId');
    }

    
}
