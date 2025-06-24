<?php

namespace Database\Seeders;

use App\Models\Permission;
use \App\Models\RolePermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //get all permissions
        $permissions = Permission::all();

        //create rolePermission for role id 1
        for ($i = 1; $i <= count($permissions); $i++) {
            $roleId = 1;
            $permissionId = $i;

            // Assuming you have a model for rolePermission with proper relationships
            $rolePermission = new RolePermission();
            $rolePermission->role()->associate($roleId);
            $rolePermission->permission()->associate($permissionId);
            $rolePermission->save();
        }

        //create rolePermission for role id 3
        $customer = [32, 33, 53, 54, 62, 63, 82, 97, 102, 152, 156, 158, 159, 283, 266, 267, 268, 269, 270, 282, 286, 288, 289];

        for ($i = 0; $i < count($customer); $i++) {
            $roleId = 3;
            $permissionId = $customer[$i];

            // Assuming you have a model for rolePermission with proper relationships
            $rolePermission = new RolePermission();
            $rolePermission->role()->associate($roleId);
            $rolePermission->permission()->associate($permissionId);
            $rolePermission->save();
        }
    }
}
