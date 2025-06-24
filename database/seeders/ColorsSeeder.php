<?php

namespace Database\Seeders;

use App\Models\Colors;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ColorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $colors = [
            [
                'name' => "Black",
                'colorCode' => "#000000",
            ]

        ];

        foreach ($colors as $item) {
            $color = new Colors();
            $color->name = $item['name'];
            $color->colorCode = $item['colorCode'];
            $color->save();
        }
    }
}
