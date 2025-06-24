<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class customerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = new Customer();
        $customer->roleId = 3;
        $customer->username = 'Doe';
        $customer->email = 'dev@omega.ac';
        $customer->password = Hash::make('1111');
        $customer->phone = '1234567890';
        $customer->address = '123 Main St';
        $customer->save();
    }
}
