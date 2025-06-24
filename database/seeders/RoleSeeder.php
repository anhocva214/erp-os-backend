<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = new Role();
        $role->name = 'super-admin';
        $role->save();

        $role = new Role();
        $role->name = 'admin';
        $role->save();

        $role = new Role();
        $role->name = 'customer';
        $role->save();

        $role = new Role();
        $role->name = 'manager';
        $role->save();

        $role = new Role();
        $role->name = 'salesman';
        $role->save();

        $role = new Role();
        $role->name = 'delivery-boy';
        $role->save();
    }
}
