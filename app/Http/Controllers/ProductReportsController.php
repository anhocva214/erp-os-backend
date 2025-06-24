<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Product;
use App\Models\ProductVat;
use Illuminate\Http\Request;
use App\Models\SaleInvoiceProduct;
use Illuminate\Support\Facades\DB;

class ProductReportsController extends Controller
{
    public function getAllProductReports(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->query('query') === 'top-selling-products') {
            try {
                $paginate = getPagination($request->query());

                //check if table is empty
                $topSellingProducts = DB::table('saleInvoiceProduct')
                    ->select('productId', DB::raw('SUM(productQuantity) as totalQuantitySold'))
                    ->groupBy('productId')
                    ->groupBy('productId')
                    ->orderByDesc('totalQuantitySold')
                    ->get();

                $topSellingProductIds = $topSellingProducts->pluck('productId')->toArray();

                $data = Product::whereIn('id', $topSellingProductIds)
                        ->orderBy(DB::raw('FIELD(id, ' . implode(',', $topSellingProductIds) . ')'))
                        ->skip($paginate['skip'])
                        ->take($paginate['limit'])
                        ->get();
                    
                $currentAppUrl = url('/');
                $converted = arrayKeysToCamelCase($data->toArray());

                foreach ($converted as $key => $value) {
                    $productVat = ProductVat::find($value['productVatId']);
                    $converted[$key]['productSalePriceWithVat'] = takeUptoThreeDecimal($value['productSalePrice'] + ($value['productSalePrice'] * ($productVat->percentage / 100)));

                    // set image url
                    if ($converted[$key]['productThumbnailImage']) {
                        $converted[$key]['productThumbnailImageUrl'] = "{$currentAppUrl}/product-image/{$converted[$key]['productThumbnailImage']}";
                    }

                    unset($converted[$key]['productPurchasePrice']);
                }

                $finalResult = [
                    'getAllTopSellingProduct' => $converted,
                    'totalTopSellingProduct' => $data->count()
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                echo $err;
                return response()->json(['error' => 'An error occurred during getting ProductReport. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'new-products') {
            try {
                $paginate = getPagination($request->query());
                $newProduct = Product::with('productSubCategory', 'productBrand','discount')
                    ->orderByDesc('created_at')
                    ->skip($paginate['skip'])
                    ->take($paginate['limit'])
                    ->get();

                $currentAppUrl =  url('/');
                $converted = arrayKeysToCamelCase($newProduct->toArray());

                // concat the productSalePrice and vat amount
                foreach ($converted as $key => $value) {
                    $productVat = ProductVat::find($value['productVatId']);
                    if ($productVat) {
                        $converted[$key]['productSalePriceWithVat'] = takeUptoThreeDecimal($value['productSalePrice'] + ($value['productSalePrice'] * ($productVat->percentage / 100)));
                    } else {
                        $converted[$key]['productSalePriceWithVat'] = takeUptoThreeDecimal($value['productSalePrice']);
                    }


                    // set image url
                    if ($converted[$key]['productThumbnailImage']) {
                        $converted[$key]['productThumbnailImageUrl'] = "{$currentAppUrl}/product-image/{$converted[$key]['productThumbnailImage']}";
                    }
                    unset($converted[$key]['productPurchasePrice']);
                }

                $finalResult = [
                    'getAllNewProduct' => $converted,
                    'totalNewProduct' => $newProduct->count()
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductReport. Please try again later.'], 500);
            }
        } else {

            return response()->json(['error' => 'An error occurred during getting ProductReport. Please try again later.'], 500);
        }
    }
}
