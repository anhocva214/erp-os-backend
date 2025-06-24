<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JetBrains\PhpStorm\Pure;

class ReportController extends Controller
{
    //generate productPurchaseInvoice Report
    public function generatePurchaseReport(Request $request): JsonResponse
    {
        if ($request->query('startDate')) {
            try {
                $startDate = Carbon::parse($request->query('startDate'));
                $endDate = Carbon::parse($request->query('endDate'));

                // Create cache key for date range
                $cacheKey = 'purchase_report_' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d');

                $result = Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate) { // 30 minutes cache
                    // Optimized query with selective loading
                    $filteredPurchaseInvoice = PurchaseInvoice::whereBetween('date', [$startDate, $endDate])
                        ->select('id')
                        ->get();
                    $filteredId = $filteredPurchaseInvoice->pluck('id');

                    if ($filteredId->isEmpty()) {
                        return [];
                    }

                    $allPurchaseProduct = PurchaseInvoiceProduct::whereIn('invoiceId', $filteredId)
                        ->with([
                            'product:id,name,sku',
                            'invoice:id,date',
                            'invoice.supplier:id,name'
                        ])
                        ->get()
                        ->groupBy('productId')
                        ->toArray();

                $modifiedResult = [];
                foreach ($allPurchaseProduct as $value) {
                    foreach ($value as $item) {
                        $productName = $item['product']['name'];
                        $SKU = $item['product']['sku'];
                        $supplier = $item['invoice']['supplier']['name'] . ',' . $item['invoice']['supplier']['address'];
                        $purchaseInvoiceId = $item['invoiceId'];
                        $purchaseInvoiceDate = Carbon::parse($item['invoice']['date'])->toDateString();
                        $quantity = (int) $item['productQuantity'];
                        $unitPurchasePrice = (float) $item['productPurchasePrice'];

                        $subTotal = $quantity * $unitPurchasePrice;


                        $modifiedResult[] =  [
                            'productName' => $productName,
                            'SKU' => $SKU,
                            'supplier' => $supplier,
                            'purchaseInvoiceId' => $purchaseInvoiceId,
                            'purchaseInvoiceDate' => $purchaseInvoiceDate,
                            'quantity' => $quantity,
                            'unitPurchasePrice' => takeUptoThreeDecimal((float) $unitPurchasePrice),
                            'subTotal' => $subTotal,
                        ];
                    }
                }

                    return $modifiedResult;
                });

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during generate product purchase report.Please try again later.'], 500);
            }
        } else {
            try {

                $allPurchaseProduct = PurchaseInvoiceProduct::with('product', 'invoice.supplier')
                    ->get()
                    ->groupBy('productId')
                    ->toArray();

                $modifiedResult = [];
                foreach ($allPurchaseProduct as $value) {
                    foreach ($value as $item) {
                        $productName = $item['product']['name'];
                        $SKU = $item['product']['sku'];
                        $supplier = $item['invoice']['supplier']['name'] . ',' . $item['invoice']['supplier']['address'];
                        $purchaseInvoiceId = $item['invoiceId'];
                        $purchaseInvoiceDate = Carbon::parse($item['invoice']['date'])->toDateString();
                        $quantity = (int) $item['productQuantity'];
                        $unitPurchasePrice = (float) $item['productPurchasePrice'];

                        $subTotal = $quantity * $unitPurchasePrice;


                        $modifiedResult[] =  [
                            'productName' => $productName,
                            'SKU' => $SKU,
                            'supplier' => $supplier,
                            'purchaseInvoiceId' => $purchaseInvoiceId,
                            'purchaseInvoiceDate' => $purchaseInvoiceDate,
                            'quantity' => $quantity,
                            'unitPurchasePrice' => takeUptoThreeDecimal((float) $unitPurchasePrice),
                            'subTotal' => $subTotal,
                        ];
                    }
                }

                return response()->json($modifiedResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during generate product purchase report.Please try again later.'], 500);
            }
        }
    }

    //generate productPurchaseInvoice Report
    public function generateStockReport(Request $request): JsonResponse
    {
        try {

            $allPurchaseProduct = PurchaseInvoiceProduct::with('product.productSubCategory.productCategory', 'invoice.supplier')
                ->get()
                ->groupBy('productId')
                ->toArray();

            $modifiedResult = [];
            foreach ($allPurchaseProduct as $value) {
                foreach ($value as $item) {
                    $productName = $item['product']['name'] ?? '';
                    $SKU = $item['product']['sku'] ?? '';
                    $uomId = $item['product']['uomId'] ?? '';
                    $uomValue = $item['product']['uomValue'] ?? '';
                    $subCategory = $item['product']['product_sub_category']['name'] ?? '';
                    $category = $item['product']['product_sub_category']['product_category']['name'] ?? '';

                    $unitPurchasePrice = (float) $item['productPurchasePrice'];
                    $unitSellingPrice = (float) $item['product']['productSalePrice'];
                    $currentStock = (int) $item['productQuantity'];

                    $stockPurchasePrice = $unitPurchasePrice * $currentStock;
                    $stockSalePrice = $unitSellingPrice * $currentStock;

                    $potentialProfit = $stockSalePrice - $stockPurchasePrice;

                    $modifiedResult[] =  [
                        'SKU' => $SKU,
                        'productName' => $productName,
                        'variation' => $uomId . ' ' . $uomValue, // Concatenate here
                        'category' => $category,
                        'subCategory' => $subCategory,
                        'unitSellingPrice' => takeUptoThreeDecimal((float) $unitSellingPrice),
                        'currentStock' => $currentStock,
                        'stockPurchasePrice' => $stockPurchasePrice,
                        'stockSalePrice' => $stockSalePrice,
                        'potentialProfit' => $potentialProfit,
                    ];
                }
            }

            return response()->json($modifiedResult, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during generate product purchase report.Please try again later.'], 500);
        }
    }
}
