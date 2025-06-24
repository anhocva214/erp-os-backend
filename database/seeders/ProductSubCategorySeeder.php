<?php

namespace Database\Seeders;

use App\Models\ProductSubCategory;
use Illuminate\Database\Seeder;

class ProductSubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $productSubCategory = new ProductSubCategory();
        $productSubCategory->name = "Household";
        $productSubCategory->productCategoryId = 1;
        $productSubCategory->save();

        $productSubCategory = new ProductSubCategory();
        $productSubCategory->name = "Mobile";
        $productSubCategory->productCategoryId = 2;
        $productSubCategory->save();

        $productSubCategory = new ProductSubCategory();
        $productSubCategory->name = "Furniture";
        $productSubCategory->productCategoryId = 3;
        $productSubCategory->save();

        $productSubCategory = new ProductSubCategory();
        $productSubCategory->name = "Laptop";
        $productSubCategory->productCategoryId = 4;
        $productSubCategory->save();

        $productSubCategory = new ProductSubCategory();
        $productSubCategory->name = "Cosmetics";
        $productSubCategory->productCategoryId = 5;
        $productSubCategory->save();

    }
}
