<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $discounts = [
            [
                'value' => '10',
                'type' => 'percentage',
                'status' => 'true',
                'startDate' => '2023-09-10',
                'endDate' => '2023-10-10',
            ],
            [
                'value' => '20',
                'type' => 'flat',
                'status' => 'true',
                'startDate' => '2023-09-10',
                'endDate' => '2023-10-10',
            ]



        ];

        foreach ($discounts as $discount) {
            \App\Models\Discount::create($discount);
        }
    }
}
