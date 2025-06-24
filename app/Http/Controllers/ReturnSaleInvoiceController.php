<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ReturnSaleInvoice;
use App\Models\ReturnSaleInvoiceProduct;
use App\Models\SaleInvoice;
use App\Models\SaleInvoiceProduct;
use App\Models\Transaction;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnSaleInvoiceController extends Controller
{
    //create returnSaleInvoice controller method
    public function createSingleReturnSaleInvoice(Request $request): JsonResponse
    {
        try {
            // get sale invoice details
            $saleInvoice = SaleInvoice::where('id', $request->input('saleInvoiceId'))
                ->with('saleInvoiceProduct', 'customer')
                ->first();

            if (!$saleInvoice) {
                return response()->json(['error' => 'No Sale Invoice Found!'], 404);
            }

            $returnSaleInvoice = ReturnSaleInvoice::where('saleInvoiceId', $request->input('saleInvoiceId'))->with('returnSaleInvoiceProduct')->get();

            if ($returnSaleInvoice) {
                foreach ($request->returnSaleInvoiceProduct as $itemFromInput) {
                    $returnQuantity = 0;
                    foreach ($returnSaleInvoice as $single) {
                        foreach ($single->returnSaleInvoiceProduct as $returnedProduct) {
                            if ($returnedProduct->saleInvoiceProductId === $itemFromInput['saleInvoiceProductId']) {
                                $returnQuantity = $returnQuantity + $returnedProduct->productQuantity;
                            }
                        }
                    }

                    $saleInvoiceProduct = SaleInvoiceProduct::where('id', $itemFromInput['saleInvoiceProductId'])
                        ->where('invoiceId', $request->input('saleInvoiceId'))
                        ->value('productQuantity');

                    if (($saleInvoiceProduct - $returnQuantity) < $itemFromInput['productQuantity']) {
                        return response()->json(['error' => 'insufficient quantity for return!'], 400);
                    }

                    if ($itemFromInput['productQuantity'] === 0) {
                        return response()->json(['error' => 'return quantity cannot be zero!'], 400);
                    }
                }
            }

            $returnableProductAmountWithDiscount = $saleInvoice->saleInvoiceProduct->map(function ($item) use ($request) {
                foreach ($request->returnSaleInvoiceProduct as $item2) {
                    if ($item->id === $item2['saleInvoiceProductId']) {
                        return ($item->productFinalAmount / $item->productQuantity) * $item2['productQuantity'];
                    }
                }
                return 0;
            });

            if ($returnableProductAmountWithDiscount->sum() === 0) {
                return response()->json(['error' => 'No product Found to refund!'], 404);
            }


            $returnableProductTax = $saleInvoice->saleInvoiceProduct->map(function ($item) use ($request) {
                foreach ($request->returnSaleInvoiceProduct as $item2) {
                    if ($item->id === $item2['saleInvoiceProductId']) {
                        return ($item->taxAmount / $item->productQuantity) * $item2['productQuantity'];
                    }
                }
                return 0;
            });

            $returnableProductPurchasePrice = $saleInvoice->saleInvoiceProduct->map(function ($item) use ($request) {
                foreach ($request->returnSaleInvoiceProduct as $item2) {
                    if ($item->id === $item2['saleInvoiceProductId']) {

                        $purchasePrice = Product::where('id', $item->productId)->value('productPurchasePrice');

                        return $purchasePrice * $item2['productQuantity'];
                    }
                }
                return 0;
            });

            //calculate the how many products are returned
            $totalReturnItem = 0;
            foreach ($request->returnSaleInvoiceProduct as $item) {
                $totalReturnItem += (int)$item['productQuantity'];
            }

            //now calculate the total return amount
            $totalReturnAmount = $returnableProductAmountWithDiscount->sum();
            $totalReturnTax = $returnableProductTax->sum();
            $totalReturnPurchasePrice = $returnableProductPurchasePrice->sum();

            $totalInstantReturnAmount = 0;
            foreach ($request->instantReturnAmount as $amountData) {
                $totalInstantReturnAmount += $amountData['amount'];
            }

             //input amount can not be greater than total return amount
             if ($totalInstantReturnAmount > $totalReturnAmount) {
                return response()->json(['error' => 'Instant return amount cannot be greater than total return amount!'], 400);
            }


            // convert all incoming date to a specific format.
            $date = Carbon::parse($request->input('date'))->toDateString();

            // create returnSaleInvoice method
            $createdReturnSaleInvoice = ReturnSaleInvoice::create([
                'date' => new DateTime($date),
                'totalAmount' => takeUptoThreeDecimal((float)$totalReturnAmount),
                'instantReturnAmount' => $totalInstantReturnAmount ?  takeUptoThreeDecimal($totalInstantReturnAmount) : 0,
                'tax' => takeUptoThreeDecimal((float)$totalReturnTax),
                'saleInvoiceId' => $request->input('saleInvoiceId'),
                'invoiceMemoNo' => $request->input('invoiceMemoNo') ? $request->input('invoiceMemoNo') : null,
                'note' => $request->input('note'),
            ]);

            if ($createdReturnSaleInvoice) {
                foreach ($request->returnSaleInvoiceProduct as $itemFromInput) {
                    foreach ($saleInvoice->saleInvoiceProduct as $itemFromDB) {
                        if ($itemFromDB->id === $itemFromInput['saleInvoiceProductId']) {

                            $productFinalAmount = ($itemFromDB->productFinalAmount / $itemFromDB->productQuantity) * $itemFromInput['productQuantity'];

                            $taxAmount = ($itemFromDB->taxAmount / $itemFromDB->productQuantity) * $itemFromInput['productQuantity'];

                            ReturnSaleInvoiceProduct::create([
                                'invoiceId' => $createdReturnSaleInvoice->id,
                                'saleInvoiceProductId' => $itemFromDB->id,
                                'productId' => (int)$itemFromDB->productId,
                                'productQuantity' => (int)$itemFromInput['productQuantity'],
                                'productUnitSalePrice' => takeUptoThreeDecimal((float)$itemFromDB->productUnitSalePrice),
                                'productFinalAmount' => takeUptoThreeDecimal((float)$productFinalAmount),
                                'tax' => takeUptoThreeDecimal((float)$itemFromDB->tax),
                                'taxAmount' => takeUptoThreeDecimal((float)$taxAmount),
                            ]);
                        }
                    }
                }
            }

            // goods received on return sale transaction create
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 3,
                'creditId' => 9,
                'amount' => takeUptoThreeDecimal((float)$totalReturnPurchasePrice),
                'particulars' => "Cost of sales reduce on Sale return Invoice #$createdReturnSaleInvoice->id of sale Invoice #{$request->input('saleInvoiceId')}",
                'type' => 'sale_return',
                'relatedId' => $request->input('saleInvoiceId'),
            ]);


            // transaction for account receivable of sales return
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 8,
                'creditId' => 4,
                'amount' => takeUptoThreeDecimal($totalReturnAmount),
                'particulars' => "Account Receivable on Sale return Invoice #$createdReturnSaleInvoice->id of sale Invoice #{$request->input('saleInvoiceId')}",
                'type' => 'sale_return',
                'relatedId' => $request->input('saleInvoiceId'),
            ]);

            // transaction for account receivable of vat return
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 15,
                'creditId' => 4,
                'amount' => takeUptoThreeDecimal($totalReturnTax),
                'particulars' => "Account Receivable on Sale return Invoice for tax #$createdReturnSaleInvoice->id of sale Invoice #{$request->input('saleInvoiceId')}",
                'type' => 'sale_return',
                'relatedId' => $request->input('saleInvoiceId'),
            ]);

            // if instant given any amount for return
            foreach ($request->instantReturnAmount as $amountData) {
                if ($amountData['amount'] > 0) {
                    Transaction::create([
                        'date' => new DateTime($date),
                        'debitId' => 4,
                        'creditId' => $amountData['paymentType'] ? $amountData['paymentType'] : 1,
                        'amount' => takeUptoThreeDecimal($amountData['amount']),
                        'particulars' => "return amount on Sale return Invoice #$createdReturnSaleInvoice->id of sale Invoice #{$request->input('saleInvoiceId')}",
                        'type' => 'sale_return',
                        'relatedId' => $request->input('saleInvoiceId'),
                    ]);
                }
            }

            // iterate through all products of this return sale invoice and increase the product quantity
            foreach ($request->returnSaleInvoiceProduct as $itemFromInput) {
                foreach ($saleInvoice->saleInvoiceProduct as $itemFromDB) {
                    if ($itemFromDB->id === $itemFromInput['saleInvoiceProductId']) {
                        Product::where('id', $itemFromDB->productId)
                            ->update([
                                'productQuantity' => DB::raw("productQuantity +  {$itemFromInput['productQuantity']}"),
                            ]);
                    }
                }
            }

            // decrease sale invoice profit by return sale invoice's calculated profit profit
            $returnSaleInvoiceProfit = $totalReturnAmount - $totalReturnPurchasePrice;

            SaleInvoice::where('id', $request->input('saleInvoiceId'))
                ->update([
                    'profit' => DB::raw("profit - $returnSaleInvoiceProfit"),
                ]);


            $converted = arrayKeysToCamelCase($createdReturnSaleInvoice->toArray());
            return response()->json($converted, 201);
        } catch (Exception $err) {
            echo $err;
            return response()->json(['error' => 'An error occurred during create ReturnSaleInvoice. Please try again later.'], 500);
        }
    }

    // get all returnSaleInvoice controller method
    public function getAllReturnSaleInvoice(Request $request): JsonResponse
    {
        if ($request->query('query') === 'info') {
            try {
                $aggregations = ReturnSaleInvoice::selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount')
                    ->first();

                $result = [
                    '_count' => [
                        'id' => $aggregations->countedId
                    ],
                    '_sum' => [
                        'totalAmount' => $aggregations->totalAmount,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ReturnSaleInvoice. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'all') {
            try {
                $allReturnSaleInvoice = ReturnSaleInvoice::with('saleInvoice.customer:id,username,email,phone,address')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $converted = arrayKeysToCamelCase($allReturnSaleInvoice->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ReturnSaleInvoice. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'group') {
            try {
                $allReturnSaleInvoice = ReturnSaleInvoice::selectRaw('date as date, SUM(totalAmount) as totalAmount, COUNT(id) as idCount')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->get();

                $converted = arrayKeysToCamelCase($allReturnSaleInvoice->toArray());
                $finalResult = collect($converted)->map(function ($item) {
                    return [
                        '_sum' => [
                            'totalAmount' => $item['totalAmount'],
                        ],
                        '_count' => [
                            'id' => $item['idCount'],
                        ],
                        'date' => $item['date'],
                    ];
                });

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ReturnSaleInvoice. Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());

                $aggregation = ReturnSaleInvoice::where('status', $request->query('status'))
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                            ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->selectRaw('COUNT(id) as idCount, SUM(totalAmount) as totalAmount')
                    ->first();

                $allReturnSaleInvoice = ReturnSaleInvoice::where('status', $request->query('status'))
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                            ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->with('saleInvoice.customer:id,username,email,phone,address')
                    ->orderBy('created_at', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $aggregations = [
                    '_count' => [
                        'id' => $aggregation->idCount,
                    ],
                    '_sum' => [
                        'totalAmount' => $aggregation->totalAmount,
                    ],
                ];

                $converted = arrayKeysToCamelCase($allReturnSaleInvoice->toArray());
                $finalResult = [
                    'aggregations' => $aggregations,
                    'allSaleInvoice' => $converted,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                echo $err;
                return response()->json(['error' => 'An error occurred during getting ReturnSaleInvoice. Please try again later.'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid query parameter'], 400);
        }
    }

    // get a single returnSaleInvoice controller method
    public function getSingleReturnSaleInvoice(Request $request, $id): JsonResponse
    {
        try {
            $singleProduct = ReturnSaleInvoice::where('id', $id)
                ->with('returnSaleInvoiceProduct', 'returnSaleInvoiceProduct.product', 'saleInvoice.customer:id,username,email,phone,address')
                ->first();

            $converted = arrayKeysToCamelCase($singleProduct->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting ReturnSaleInvoice. Please try again later.'], 500);
        }
    }

    // delete a single returnSaleInvoice controller method
    // on delete purchase invoice, decrease product quantity, customer due amount decrease, transaction create
    public function deleteSingleReturnSaleInvoice(Request $request, $id): JsonResponse
    {
        try {
            // get returnSaleInvoice details
            $returnSaleInvoice = ReturnSaleInvoice::where('id', (int)$id)
                ->with('returnSaleInvoiceProduct', 'returnSaleInvoiceProduct.product')
                ->first();

            // product quantity decrease
            foreach ($returnSaleInvoice->returnSaleInvoiceProduct as $item) {
                $productId = (int)$item['productId'];
                $productQuantity = (int)$item['productQuantity'];

                Product::where('id', $productId)->update([
                    'productQuantity' => DB::raw("productQuantity - $productQuantity"),
                ]);
            }

            $deletedReturnSaleInvoice = ReturnSaleInvoice::where('id', (int)$id)
                ->update([
                    'status' => $request->input('status'),
                ]);

            if (!$deletedReturnSaleInvoice) {
                return response()->json(['error' => 'Failed To Delete ReturnSaleInvoice'], 404);
            }

            return response()->json(['message' => 'Return Sale Invoice deleted successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete ReturnSaleInvoice. Please try again later.'], 500);
        }
    }
}
