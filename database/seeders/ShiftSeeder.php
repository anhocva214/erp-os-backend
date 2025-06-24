<?php

namespace Database\Seeders;

use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use App\Models\Shift;

class ShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $shift = new Shift();
        $shift->name = 'Demo Shift';
        $shift->startTime = Carbon::createFromFormat('H:i:s', '09:00:00')->format('H:i:s');
        $shift->endTime = Carbon::createFromFormat('H:i:s', '15:00:00')->format('H:i:s');
        $shift->workHour = 8;
        $shift->save();

       
    }
}
