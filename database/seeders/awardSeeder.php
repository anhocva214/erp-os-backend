<?php

namespace Database\Seeders;

use App\Models\Award;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class awardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $award = new Award();
        $award->name = 'Demo Award';
        $award->description = 'Demo Award Description';
        $award->image = 'https://i.imgur.com/3Lm2Wwv.png';
        $award->save();

    }
}
