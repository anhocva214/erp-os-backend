<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\hasMany;

class EmploymentStatus extends Model
{
    use HasFactory;

    //create employmentStatus model
    protected $table = 'employmentStatus';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        "colourValue",
        'description',
    ];

    public function user(): hasMany
    {
        return $this->hasMany(Users::class, 'employmentStatusId');
    }
}
