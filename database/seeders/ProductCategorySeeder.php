<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productCategory = new ProductCategory();
        $productCategory->name = 'Category 1';
        $productCategory->save();

        $productCategory = new ProductCategory();
        $productCategory->name = 'Category 2';
        $productCategory->save();

        $productCategory = new ProductCategory();
        $productCategory->name = 'Category 3';
        $productCategory->save();

        $productCategory = new ProductCategory();
        $productCategory->name = 'Category 4';
        $productCategory->save();

        $productCategory = new ProductCategory();
        $productCategory->name = 'Category 5';
        $productCategory->save();
    }
}
