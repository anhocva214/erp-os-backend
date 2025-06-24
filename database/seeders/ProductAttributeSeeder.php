<?php

namespace Database\Seeders;


use App\Models\ProductAttribute;
use Illuminate\Database\Seeder;

class ProductAttributeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productAttribute = new ProductAttribute();
        $productAttribute->name = 'Demo Attribute';
        $productAttribute->save();

    }
}
