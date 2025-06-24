<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Users extends Model
{
    use HasFactory;

    //create user model
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $hidden = ['refreshToken', 'password', 'isLogin'];
    protected $fillable = [
        'firstName',
        'lastName',
        'username',
        'email',
        'refreshToken',
        'salary',
        'employeeId',
        'phone',
        'street',
        'city',
        'state',
        'zipCode',
        'bloodGroup',
        'image',
        'joinDate',
        'leaveDate',
        'password',
        'roleId',
        'designationId',
        'employmentStatusId',
        'departmentId',
        'shiftId',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'roleId');
    }

    public function saleInvoice(): HasMany
    {
        return $this->hasMany(SaleInvoice::class, 'userId');
    }

    public function adjustment(): HasMany
    {
        return $this->hasMany(AdjustInvoice::class, 'userId');
    }

    public function quote(): HasMany
    {
        return $this->hasMany(Quote::class, 'quoteOwnerId');
    }


    public function designationHistory(): HasMany
    {
        return $this->hasMany(DesignationHistory::class, 'userId');
    }
    public function salaryHistory(): HasMany
    {
        return $this->hasMany(SalaryHistory::class, 'userId');
    }
    public function awardHistory(): HasMany
    {
        return $this->hasMany(AwardHistory::class, 'userId');
    }
    public function education(): HasMany
    {
        return $this->hasMany(Education::class, 'userId');
    }
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shiftId');
    }
    public function employmentStatus(): BelongsTo
    {
        return $this->belongsTo(EmploymentStatus::class, 'employmentStatusId');
    }
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'departmentId');
    }




}
