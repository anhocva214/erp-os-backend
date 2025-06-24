<?php

namespace Database\Seeders;

use App\Models\ProductColor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productColors = [
            [
                'productId' => 1,
                'colorId' => 1,
            ]
        ];

        foreach ($productColors as $item) {
            $productColor = new ProductColor();
            $productColor->productId = $item['productId'];
            $productColor->colorId = $item['colorId'];
            $productColor->save();
        }
    }
}
