<?php

namespace Database\Seeders;

use App\Models\UoM;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UoMSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uom = new UoM();
        $uom->name = 'pc';
        $uom->save();

        $uom = new UoM();
        $uom->name = 'kg';
        $uom->save();

        $uom = new UoM();
        $uom->name = 'ltr';
        $uom->save();

        $uom = new UoM();
        $uom->name = 'ml';
        $uom->save();

        $uom = new UoM();
        $uom->name = 'gm';
        $uom->save();
    }
}
