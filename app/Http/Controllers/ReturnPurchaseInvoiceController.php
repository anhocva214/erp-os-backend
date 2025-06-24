<?php

namespace App\Http\Controllers;

use App\Models\{Product, Transaction, PurchaseInvoice, PurchaseInvoiceProduct, ReturnPurchaseInvoice, ReturnPurchaseInvoiceProduct};
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;

class ReturnPurchaseInvoiceController extends Controller
{
    //create returnPurchaseInvoice controller method
    public function createSingleReturnPurchaseInvoice(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            // get purchase invoice details
            $purchaseInvoice = PurchaseInvoice::where('id', $request->input('purchaseInvoiceId'))
                ->with('purchaseInvoiceProduct', 'supplier')
                ->first();

            if (!$purchaseInvoice) {
                return response()->json(['error' => 'No Purchase Invoice Found!'], 404);
            }

            $returnPurchaseInvoice = ReturnPurchaseInvoice::where('purchaseInvoiceId', $request->input('purchaseInvoiceId'))
            ->with('returnPurchaseInvoiceProduct')
            ->get();

            if ($returnPurchaseInvoice) {
                foreach ($request->returnPurchaseInvoiceProduct as $itemFromInput) {
                    $returnQuantity = 0;
                    foreach ($returnPurchaseInvoice as $single) {
                        foreach ($single->returnPurchaseInvoiceProduct as $returnedProduct) {
                            if ($returnedProduct->purchaseInvoiceProductId === $itemFromInput['purchaseInvoiceProductId']) {
                                $returnQuantity = $returnQuantity + $returnedProduct->productQuantity;
                            }
                        }
                    }

                    $purchaseInvoiceProduct = PurchaseInvoiceProduct::where('id', $itemFromInput['purchaseInvoiceProductId'])
                        ->where('invoiceId', $request->input('purchaseInvoiceId'))
                        ->value('productQuantity');

                    if (($purchaseInvoiceProduct - $returnQuantity) < $itemFromInput['productQuantity']) {
                        return response()->json(['error' => 'insufficient quantity for return!'], 400);
                    }

                    if ($itemFromInput['productQuantity'] === 0) {
                        return response()->json(['error' => 'return quantity cannot be zero!'], 400);
                    }
                }
            }


            $returnableProductAmount = $purchaseInvoice->purchaseInvoiceProduct->map(function ($item) use ($request) {
                foreach ($request->returnPurchaseInvoiceProduct as $item2) {
                    if ($item->id === $item2['purchaseInvoiceProductId']) {
                        return ($item->productFinalAmount / $item->productQuantity) * $item2['productQuantity'];
                    }
                }
            });

            if ($returnableProductAmount->sum() === 0) {
                return response()->json(['error' => 'No product Found to refund!'], 404);
            }


            $returnableProductTax = $purchaseInvoice->purchaseInvoiceProduct->map(function ($item) use ($request) {
                foreach ($request->returnPurchaseInvoiceProduct as $item2) {
                    if ($item->id === $item2['purchaseInvoiceProductId']) {
                        return ($item->taxAmount / $item->productQuantity) * $item2['productQuantity'];
                    }
                }
            });

            //calculate the how many products are returned
            $totalReturnItem = 0;
            foreach ($request->returnPurchaseInvoiceProduct as $item) {
                $totalReturnItem += (int)$item['productQuantity'];
            }

            //now calculate the total return amount
            $totalReturnAmount = $returnableProductAmount->sum();
            $totalReturnTax = $returnableProductTax->sum();

            $totalInstantReturnAmount = 0;
            foreach ($request->instantReturnAmount as $amountData) {
                $totalInstantReturnAmount += $amountData['amount'];
            }

             //input amount can not be greater than total return amount
             if ($totalInstantReturnAmount > $totalReturnAmount) {
                DB::rollBack();
                return response()->json(['error' => 'Amount cannot be greater than total return amount!'], 400);
            }

            $date = Carbon::parse($request->input('date'))->toDateString();
            $createdReturnPurchaseInvoice = ReturnPurchaseInvoice::create([
                'date' => new DateTime($date),
                'totalAmount' => takeUptoThreeDecimal((float)$totalReturnAmount),
                'tax' => takeUptoThreeDecimal((float)$totalReturnTax),
                'instantReturnAmount' => $totalInstantReturnAmount ? takeUptoThreeDecimal((float)$totalInstantReturnAmount) : 0,
                'purchaseInvoiceId' => $request->input('purchaseInvoiceId'),
                'invoiceMemoNo' => $request->input('invoiceMemoNo') ? $request->input('invoiceMemoNo') : null,
                'note' => $request->input('note'),
            ]);


            if ($createdReturnPurchaseInvoice) {
                foreach ($request->returnPurchaseInvoiceProduct as $itemFromInput) {
                    foreach ($purchaseInvoice->purchaseInvoiceProduct as $itemFromDB) {
                        if ($itemFromDB->id === $itemFromInput['purchaseInvoiceProductId']) {

                            $productFinalAmount = ($itemFromDB->productFinalAmount / $itemFromDB->productQuantity) * $itemFromInput['productQuantity'];

                            $taxAmount = ($itemFromDB->taxAmount / $itemFromDB->productQuantity) * $itemFromInput['productQuantity'];

                            ReturnPurchaseInvoiceProduct::create([
                                'invoiceId' => $createdReturnPurchaseInvoice->id,
                                'productId' => (int)$itemFromDB->productId,
                                'purchaseInvoiceProductId' => (int)$itemFromDB->id,
                                'productQuantity' => (int)$itemFromInput['productQuantity'],
                                'productUnitPurchasePrice' => takeUptoThreeDecimal((float)$itemFromDB->productUnitPurchasePrice),
                                'productFinalAmount' => takeUptoThreeDecimal($productFinalAmount),
                                'tax' => takeUptoThreeDecimal((float)$itemFromDB->tax),
                                'taxAmount' => takeUptoThreeDecimal((float)$taxAmount),
                            ]);
                        }
                    }
                }
            }

            // transaction for purchase return account payable for total return amount without tax
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 5,
                'creditId' => 3,
                'amount' => takeUptoThreeDecimal($totalReturnAmount),
                'particulars' => "return total amount of return invoice #$createdReturnPurchaseInvoice->id and referenced Purchase Invoice #{$request->input('purchaseInvoiceId')}",
                'type' => 'purchase_return',
                'relatedId' => $request->input('purchaseInvoiceId'),
            ]);

            // transaction for purchase return account payable for return tax amount
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 5,
                'creditId' => 15,
                'amount' => takeUptoThreeDecimal($totalReturnTax),
                'particulars' => "return tax amount of return invoice #$createdReturnPurchaseInvoice->id and referenced Purchase Invoice #{$request->input('purchaseInvoiceId')}",
                'type' => 'purchase_return',
                'relatedId' => $request->input('purchaseInvoiceId'),
            ]);

            // pay on purchase transaction for return amount
            foreach ($request->instantReturnAmount as $amountData) {
                if ($amountData['amount'] > 0) {
                    Transaction::create([
                        'date' => new DateTime($date),
                        'debitId' => $amountData['paymentType'] ? $amountData['paymentType'] : 1,
                        'creditId' => 5,
                        'amount' => takeUptoThreeDecimal((float)$amountData['amount']),
                        'particulars' =>  "return paid amount of return invoice #$createdReturnPurchaseInvoice->id and referenced Purchase Invoice #{$request->input('purchaseInvoiceId')}",
                        'type' => 'purchase_return',
                        'relatedId' => $request->input('purchaseInvoiceId'),
                    ]);
                }
            }

            // iterate through all products of this return purchase invoice and decrement the product quantity,
            foreach ($request->returnPurchaseInvoiceProduct as $itemFromInput) {
                foreach ($purchaseInvoice->purchaseInvoiceProduct as $itemFromDB) {
                    if ($itemFromDB->id === $itemFromInput['purchaseInvoiceProductId']) {
                        Product::where('id', $itemFromDB->productId)
                            ->update([
                                'productQuantity' => DB::raw("productQuantity -  {$itemFromInput['productQuantity']}"),
                            ]);
                    }
                }
            }

            $converted = arrayKeysToCamelCase($createdReturnPurchaseInvoice->toArray());
            Db::commit();
            return response()->json(["createdReturnPurchaseInvoice" => $converted], 201);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred during create ReturnPurchaseInvoice. Please try again later.', $err->getMessage()], 500);
        }
    }

    // get all the returnPurchaseInvoice controller method
    public function getAllReturnPurchaseInvoice(Request $request): JsonResponse
    {
        if ($request->query('query') === 'info') {
            try {
                $aggregations = ReturnPurchaseInvoice::selectRaw('COUNT(id) as id, SUM(totalAmount) as totalAmount')
                    ->first();

                $result = [
                    '_count' => [
                        'id' => $aggregations->id
                    ],
                    '_sum' => [
                        'totalAmount' => $aggregations->totalAmount,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ReturnPurchaseInvoice. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'all') {
            try {
                $allReturnPurchaseInvoice = ReturnPurchaseInvoice::with('purchaseInvoice.supplier')
                    ->orderBy('created_at', 'desc')
                    ->get();

                $converted = arrayKeysToCamelCase($allReturnPurchaseInvoice->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ReturnPurchaseInvoice. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'group') {
            try {
                $allReturnPurchaseInvoice = ReturnPurchaseInvoice::selectRaw('date as date, SUM(totalAmount) as totalAmount, COUNT(id) as idCount')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->get();

                $converted = arrayKeysToCamelCase($allReturnPurchaseInvoice->toArray());
                $finalResult = collect($converted)->map(function ($item) {
                    $modifiedInvoice = [
                        '_sum' => [
                            'totalAmount' => $item['totalAmount'],
                        ],
                        '_count' => [
                            'id' => $item['idCount'],
                        ],
                        'date' => $item['date'],
                    ];

                    return $modifiedInvoice;
                });

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ReturnPurchaseInvoice. Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());

                $aggregation = ReturnPurchaseInvoice::where('status', $request->query('status'))
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                            ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->selectRaw('COUNT(id) as idCount, SUM(totalAmount) as totalAmount')
                    ->first();

                $allPurchaseInvoice = ReturnPurchaseInvoice::where('status', $request->query('status'))
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                            ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->with('purchaseInvoice.supplier')
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

                $converted = arrayKeysToCamelCase($allPurchaseInvoice->toArray());
                $finalResult = [
                    'aggregations' => $aggregations,
                    'allPurchaseInvoice' => $converted,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ReturnPurchaseInvoice. Please try again later.'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid query parameter'], 400);
        }
    }

    // get a single returnPurchaseInvoice controller method
    public function getSingleReturnPurchaseInvoice(Request $request, $id): JsonResponse
    {
        try {
            $singleProduct = ReturnPurchaseInvoice::where('id', (int)$id)
                ->with('returnPurchaseInvoiceProduct', 'returnPurchaseInvoiceProduct.product', 'purchaseInvoice.supplier')
                ->first();

            $converted = arrayKeysToCamelCase($singleProduct->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting ReturnPurchaseInvoice. Please try again later.'], 500);
        }
    }

    // on delete purchase invoice, decrease product quantity, supplier due amount decrease, transaction create
    public function deleteSingleReturnPurchaseInvoice(Request $request, $id): JsonResponse
    {
        try {
            // get purchaseInvoice details
            $returnPurchaseInvoice = ReturnPurchaseInvoice::where('id', (int)$id)
                ->with('returnPurchaseInvoiceProduct', 'returnPurchaseInvoiceProduct.product')
                ->first();

            // product quantity increase
            foreach ($returnPurchaseInvoice->returnPurchaseInvoiceProduct as $item) {
                $productId = (int)$item['productId'];
                $productQuantity = (int)$item['productQuantity'];

                Product::where('id', $productId)->update([
                    'productQuantity' => DB::raw("productQuantity + $productQuantity"),
                ]);
            }

            $deletePurchaseInvoice = ReturnPurchaseInvoice::where('id', (int)$id)
                ->update([
                    'status' => $request->input('status'),
                ]);

            if (!$deletePurchaseInvoice) {
                return response()->json(['error' => 'Failed To Delete ReturnPurchaseInvoice'], 404);
            }

            return response()->json(['message' => 'Return Purchase Invoice deleted successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete ReturnPurchaseInvoice. Please try again later.'], 500);
        }
    }
}
