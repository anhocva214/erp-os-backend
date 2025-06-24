<?php

namespace Database\Seeders;

//use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Carbon\Carbon;
use App\Models\Users;
use App\Models\Education;
use App\Models\SalaryHistory;
use Illuminate\Database\Seeder;
use App\Models\DesignationHistory;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $user = new Users();
        $user->firstName = 'John';
        $user->lastName = 'Doe';
        $user->username = 'demo';
        $user->email = 'admin@gmail.com';
        $user->phone = '01700000000';
        $user->street = 'Dhanmondi';
        $user->city = 'Dhaka';
        $user->state = 'Dhaka';
        $user->zipCode = '1205';
        $user->country = 'Bangladesh';
        $user->bloodGroup = 'A+';
        $user->joinDate = '2021-07-10';
        $user->designationId = 1;
        $user->employmentStatusId = 1;
        $user->departmentId = 1;
        $user->shiftId = 1;
        $user->password = Hash::make('5555');
        $user->roleId = 1;
        $user->employeeId = 1001;
        $user->save();

        $designation = new DesignationHistory();
        $designation->userId = 1;
        $designation->designationId = 1;
        $designation->startDate = '2021-01-01';
        $designation->comment = 'Admin';
        $designation->save();

        $education = new Education();
        $education->userId = 1;
        $education->degree = 'BSc in CSE';
        $education->institution = 'Daffodil International University';
        $education->fieldOfStudy = 'CSE';
        $education->result = '3.5';
        $education->studyStartDate = '2017-01-01';
        $education->studyEndDate = '2021-01-01';
        $education->save();

        $salary = new SalaryHistory();
        $salary->userId = 1;
        $salary->salary = 10000;
        $salary->comment = 'Monthly';
        $salary->startDate = '2021-01-01';
        $salary->save();


        $user = new Users();
        $user->firstName = 'Mr.';
        $user->lastName = 'Admin';
        $user->username = 'admin';
        $user->password = Hash::make('admin');
        $user->roleId = 2;
        $user->employeeId = 1002;
        $user->save();

        $user = new Users();
        $user->firstName = 'Mr.';
        $user->lastName = 'Customer';
        $user->username = 'customer';
        $user->password = Hash::make('customer');
        $user->roleId = 3;
        $user->employeeId = 1003;
        $user->save();

        $user = new Users();
        $user->firstName = 'Mrs.';
        $user->lastName = 'Manager';
        $user->username = 'manager';
        $user->password = Hash::make('manager');
        $user->roleId = 4;
        $user->employeeId = 1004;
        $user->save();

        $user = new Users();
        $user->firstName = 'Mr.';
        $user->lastName = 'Salesman';
        $user->username = 'salesman';
        $user->password = Hash::make('salesman');
        $user->roleId = 5;
        $user->employeeId = 1005;
        $user->save();

        $user = new Users();
        $user->firstName = 'Mrs.';
        $user->lastName = 'Delivery';
        $user->username = 'delivery';
        $user->password = Hash::make('delivery');
        $user->roleId = 6;
        $user->employeeId = 1006;
        $user->save();

    }
}
