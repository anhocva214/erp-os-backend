<?php

namespace Database\Seeders;


use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;

class ProductAttributeValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productAttributeValue = new ProductAttributeValue();
        $productAttributeValue->productAttributeId = 1;
        $productAttributeValue->name = 'Demo Attribute Value';
        $productAttributeValue->save();

     
    }
}
