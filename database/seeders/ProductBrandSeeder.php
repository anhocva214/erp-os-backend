<?php

namespace Database\Seeders;

use App\Models\ProductBrand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            "Brand 1",
            "Brand 2",
            "Brand 3",
            "Brand 4",
            "Brand 5",
        ];

        foreach ($brands as $brand) {
            $productBrand = new ProductBrand();
            $productBrand->name = $brand;
            $productBrand->save();
        }
    }
}
