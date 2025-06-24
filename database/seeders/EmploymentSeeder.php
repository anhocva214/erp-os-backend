<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use \App\Models\EmploymentStatus;

class EmploymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $employment = new EmploymentStatus();
        $employment->name = 'Demo Employment';
        $employment->colourValue = '#00FF00';
        $employment->description = 'Demo Employment Description';
        $employment->save();

    }
}
