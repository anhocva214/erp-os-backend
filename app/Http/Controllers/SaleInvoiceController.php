<?php

namespace App\Http\Controllers;

use DateTime;
use Exception;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\SaleInvoice;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ReturnSaleInvoice;
use Illuminate\Http\JsonResponse;
use App\Models\SaleInvoiceProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use function Laravel\Prompts\error;

class SaleInvoiceController extends Controller
{
    // create a single SaleInvoice controller method
    public function createSingleSaleInvoice(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {

            $validate = validator($request->all(), [
                'date' => 'required|date',
                'saleInvoiceProduct' => 'required|array|min:1',
                'saleInvoiceProduct.*.productId' => 'required|integer|distinct|exists:product,id',
                'saleInvoiceProduct.*.productQuantity' => 'required|integer|min:1',
                'saleInvoiceProduct.*.productUnitSalePrice' => 'required|numeric|min:0',
                'customerId' => 'required|integer|exists:customer,id',
                'userId' => 'required|integer|exists:users,id',
            ]);

            if ($validate->fails()) {
                return response()->json(['error' => $validate->errors()->first()], 400);
            }

            // Get all the product
            $allProducts = collect($request->input('saleInvoiceProduct'))->map(function ($item) {
                return Product::where('id', (int)$item['productId'])
                    ->first();
            });

            $totalDiscount = 0; // its discount amount
            $totalTax = 0; //its only total vat amount
            $totalSalePriceWithDiscount = 0;  //its total amount included discount but excluded vat

            foreach ($request->saleInvoiceProduct as $item) {
                $productFinalAmount = ((int)$item['productQuantity'] * (float)$item['productUnitSalePrice']) - (float)$item['productDiscount'];

                $totalDiscount = $totalDiscount + (float)$item['productDiscount'];
                $taxAmount = ($productFinalAmount * (float)$item['tax']) / 100;

                $totalTax = $totalTax + $taxAmount;
                $totalSalePriceWithDiscount += $productFinalAmount;
            }
            // Check if any product is out of stock
            $requestedProducts = collect($request->input('saleInvoiceProduct'));
            $filteredProducts = $requestedProducts->filter(function ($item) use ($allProducts) {
                $product = $allProducts->firstWhere('id', $item['productId']);
                if ($product) {
                    return $item['productQuantity'] <= $product->productQuantity;
                }
                return false;
            });
            if ($filteredProducts->count() !== $requestedProducts->count()) {
                return response()->json(['error' => 'products are out of stock'], 400);
            }

            // calculate total purchase price
            $totalPurchasePrice = 0;
            foreach ($request->saleInvoiceProduct as $item) {
                $product = $allProducts->firstWhere('id', $item['productId']);
                $totalPurchasePrice += (float)$product->productPurchasePrice * (float)$item['productQuantity'];
            }

            $totalPaidAmount = 0;
            foreach ($request->paidAmount as $amountData) {
                $totalPaidAmount += $amountData['amount'];
            }

            if($totalPaidAmount > $totalSalePriceWithDiscount + $totalTax){
                return response()->json(['error' => 'Paid amount cannot be greater than total amount!'], 400);
            }


            // Due amount
            $due = $totalSalePriceWithDiscount + $totalTax - (float)$totalPaidAmount;


            // Convert all incoming date to a specific format
            $date = Carbon::parse($request->input('date'));
            $dueDate = $request->input('dueDate') ? Carbon::parse($request->input('dueDate')) : null;

            // Create sale invoice
            $createdInvoice = SaleInvoice::create([
                'date' => $date,
                'invoiceMemoNo' => $request->input('invoiceMemoNo') ? $request->input('invoiceMemoNo') : null,
                'totalAmount' => takeUptoThreeDecimal($totalSalePriceWithDiscount),
                'totalTaxAmount' => $totalTax ? takeUptoThreeDecimal($totalTax) : 0,
                'totalDiscountAmount' => $totalDiscount ? takeUptoThreeDecimal($totalDiscount) : 0,
                'paidAmount' => $totalPaidAmount ? takeUptoThreeDecimal((float)$totalPaidAmount) : 0,
                'profit' => takeUptoThreeDecimal($totalSalePriceWithDiscount - $totalPurchasePrice),
                'dueAmount' => $due ? takeUptoThreeDecimal($due) : 0,
                'note' => $request->input('note') ?? null,
                'address' => $request->input('address'),
                'dueDate' => $dueDate,
                'termsAndConditions' => $request->input('termsAndConditions') ?? null,
                'orderStatus' => $due > 0 ? 'PENDING' : 'RECEIVED',
                'customerId' => $request->input('customerId'),
                'userId' => $request->input('userId'),
            ]);


            foreach ($request->saleInvoiceProduct as $item) {
                $productFinalAmount = ((int)$item['productQuantity'] * (float)$item['productUnitSalePrice']) - (float)$item['productDiscount'];

                $taxAmount = ($productFinalAmount * (float)$item['tax']) / 100;

                SaleInvoiceProduct::create([
                    'invoiceId' => $createdInvoice->id,
                    'productId' => (int)$item['productId'],
                    'productQuantity' => (int)$item['productQuantity'],
                    'productUnitSalePrice' => takeUptoThreeDecimal((float)$item['productUnitSalePrice']),
                    'productDiscount' => takeUptoThreeDecimal((float)$item['productDiscount']),
                    'productFinalAmount' => takeUptoThreeDecimal($productFinalAmount),
                    'tax' => $item['tax'],
                    'taxAmount' => takeUptoThreeDecimal($taxAmount),
                ]);
            }

            // cost of sales will be created as journal entry
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 9,
                'creditId' => 3,
                'amount' => takeUptoThreeDecimal((float)$totalPurchasePrice),
                'particulars' => "Cost of sales on Sale Invoice #$createdInvoice->id",
                'type' => 'sale',
                'relatedId' => $createdInvoice->id,
            ]);

            // transaction for account receivable of sales
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 4,
                'creditId' => 8,
                'amount' => takeUptoThreeDecimal($totalSalePriceWithDiscount),
                'particulars' => "total sale price with discount on Sale Invoice #$createdInvoice->id",
                'type' => 'sale',
                'relatedId' => $createdInvoice->id,
            ]);

            // transaction for account receivable of vat
            Transaction::create([
                'date' => new DateTime($date),
                'debitId' => 4,
                'creditId' => 15,
                'amount' => takeUptoThreeDecimal($totalTax),
                'particulars' => "Tax on Sale Invoice #$createdInvoice->id",
                'type' => 'sale',
                'relatedId' => $createdInvoice->id,
            ]);

            // new transactions will be created as journal entry for paid amount
            foreach ($request->paidAmount as $amountData) {
                if ($amountData['amount'] > 0) {
                    Transaction::create([
                        'date' => new DateTime($date),
                        'debitId' => $amountData['paymentType'] ? $amountData['paymentType'] : 1,
                        'creditId' => 4,
                        'amount' => takeUptoThreeDecimal($amountData['amount']),
                        'particulars' => "Payment receive on Sale Invoice #$createdInvoice->id",
                        'type' => 'sale',
                        'relatedId' => $createdInvoice->id,
                    ]);
                }
            }

            // iterate through all products of this sale invoice and decrease product quantity
            foreach ($request->input('saleInvoiceProduct') as $item) {
                $productId = (int)$item['productId'];
                $productQuantity = (int)$item['productQuantity'];

                Product::where('id', $productId)->update([
                    'productQuantity' => DB::raw("productQuantity - $productQuantity"),
                ]);
            }

            $converted = arrayKeysToCamelCase($createdInvoice->toArray());
            DB::commit();

            return response()->json(['createdInvoice' => $converted], 201);
        } catch (Exception $err) {
            echo $err;
            DB::rollBack();
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // get all the saleInvoice controller method
    public function getAllSaleInvoice(Request $request): JsonResponse
    {
        if ($request->query('query') === 'info') {
            try {
                // Cache key for sale invoice info
                $cacheKey = 'sale_invoice_info_aggregation';

                $result = Cache::remember($cacheKey, 300, function () { // Cache for 5 minutes
                    // Get sale invoice aggregation
                    $aggregation = SaleInvoice::selectRaw('COUNT(id) as id, SUM(profit) as profit')
                        ->where('isHold', 'false')
                        ->first();

                    // Single optimized query for all transaction aggregations
                    $transactionAggregations = DB::select("
                        SELECT
                            SUM(CASE WHEN type = 'sale' AND debitId = 4 THEN amount ELSE 0 END) as totalAmount,
                            SUM(CASE WHEN type = 'sale' AND creditId = 4 THEN amount ELSE 0 END) as paidAmount,
                            SUM(CASE WHEN type = 'sale_return' AND creditId = 4 THEN amount ELSE 0 END) as totalReturnAmount,
                            SUM(CASE WHEN type = 'sale_return' AND debitId = 4 THEN amount ELSE 0 END) as instantReturnAmount
                        FROM transaction
                        WHERE (type = 'sale' AND (debitId = 4 OR creditId = 4))
                           OR (type = 'sale_return' AND (creditId = 4 OR debitId = 4))
                    ");

                    $transData = $transactionAggregations[0];

                    // Calculate due amount
                    $totalDueAmount = (($transData->totalAmount - $transData->totalReturnAmount) - $transData->paidAmount) + $transData->instantReturnAmount;

                    return [
                        '_count' => [
                            'id' => $aggregation->id ?? 0
                        ],
                        '_sum' => [
                            'totalAmount' => takeUptoThreeDecimal($transData->totalAmount ?? 0),
                            'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                            'paidAmount' => takeUptoThreeDecimal($transData->paidAmount ?? 0),
                            'totalReturnAmount' => takeUptoThreeDecimal($transData->totalReturnAmount ?? 0),
                            'instantPaidReturnAmount' => takeUptoThreeDecimal($transData->instantReturnAmount ?? 0),
                            'profit' => takeUptoThreeDecimal($aggregation->profit ?? 0),
                        ],
                    ];
                });

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());

                $allSaleInvoice = SaleInvoice::where('id', $request->query('key'))
                    ->with('saleInvoiceProduct', 'user:id,firstName,lastName,username', 'customer:id,username')
                    ->orderBy('created_at', 'desc')
                    ->where('isHold', 'false')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $total = SaleInvoice::where('id', $request->query('key'))
                    ->count();

                $saleInvoicesIds = $allSaleInvoice->pluck('id')->toArray();
                // modify data to actual data of sale invoice's current value by adjusting with transactions and returns
                // transaction of the total amount
                $totalAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->orWhere('creditId', 4);
                    })
                    ->get();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('creditId', 4);
                    })
                    ->get();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // calculate grand total due amount
                $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');


                $allSaleInvoice = $allSaleInvoice->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {

                    $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;

                    $item->paidAmount = takeUptoThreeDecimal($totalPaid);
                    $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                    $item->dueAmount = takeUptoThreeDecimal($totalDueAmount);
                    $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                    return $item;
                });

                $converted = arrayKeysToCamelCase($allSaleInvoice->toArray());
                $totaluomValue = $allSaleInvoice->sum('totaluomValue');
                $totalUnitQuantity = $allSaleInvoice->map(function ($item) {
                    return $item->saleInvoiceProduct->sum('productQuantity');
                })->sum();

                return response()->json([
                    'aggregations' => [
                        '_count' => [
                            'id' => $total,
                        ],
                        '_sum' => [
                            'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                            'paidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                            'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                            'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                            'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                            'profit' => takeUptoThreeDecimal($allSaleInvoice->sum('profit')),
                            'totaluomValue' => $totaluomValue,
                            'totalUnitQuantity' => $totalUnitQuantity,
                        ],
                    ],
                    'getAllSaleInvoice' => $converted,
                    'totalSaleInvoice' => $total,
                ], 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'search-order') {
            try {
                $allOrder = SaleInvoice::where(function ($query) use ($request) {
                    if ($request->has('status')) {
                        $status = $request->query('status');
                        $query->where('orderStatus', 'LIKE', "%$status%");
                    }
                })
                    ->with('saleInvoiceProduct')
                    ->orderBy('created_at', 'desc')
                    ->where('isHold', 'false')
                    ->get();

                $converted = arrayKeysToCamelCase($allOrder->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'report') {
            try {
                $allOrder = SaleInvoice::with('saleInvoiceProduct', 'user:id,firstName,lastName,username', 'customer:id,username', 'saleInvoiceProduct.product:id,name')
                    ->where('isHold', 'false')
                    ->orderBy('created_at', 'desc')
                    ->when($request->query('salePersonId'), function ($query) use ($request) {
                        return $query->where('userId', $request->query('salePersonId'));
                    })
                    ->when($request->query('customerId'), function ($query) use ($request) {
                        return $query->where('customerId', $request->query('customerId'));
                    })
                    ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                        return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                            ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    })
                    ->get();

                $saleInvoicesIds = $allOrder->pluck('id')->toArray();
                // modify data to actual data of sale invoice's current value by adjusting with transactions and returns
                $totalAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // transaction of the paidAmount
                $totalPaidAmount = Transaction::where('type', 'sale')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->orWhere('creditId', 4);
                    })
                    ->get();

                // transaction of the total amount
                $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('creditId', 4);
                    })
                    ->get();

                // transaction of the total instant return
                $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                    ->whereIn('relatedId', $saleInvoicesIds)
                    ->where(function ($query) {
                        $query->where('debitId', 4);
                    })
                    ->get();

                // calculate grand total due amount
                $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

                // calculate paid amount and due amount of individual sale invoice from transactions and returnSaleInvoice and attach it to saleInvoices
                $allSaleInvoice = $allOrder->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {

                    $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->creditId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                        return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->debitId === 4);
                    })->reduce(function ($acc, $current) {
                        return $acc + $current->amount;
                    }, 0);

                    $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;


                    $item->paidAmount = takeUptoThreeDecimal($totalPaid);
                    $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                    $item->dueAmount = takeUptoThreeDecimal($totalDueAmount);
                    $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                    return $item;
                });

                $converted = arrayKeysToCamelCase($allSaleInvoice->toArray());
                $totaluomValue = $allSaleInvoice->sum('totaluomValue');
                $totalUnitQuantity = $allSaleInvoice->map(function ($item) {
                    return $item->saleInvoiceProduct->sum('productQuantity');
                })->sum();


                $counted = $allOrder->count();
                return response()->json([
                    'aggregations' => [
                        '_count' => [
                            'id' => $counted,
                        ],
                        '_sum' => [
                            'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                            'paidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                            'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                            'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                            'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                            'profit' => takeUptoThreeDecimal($allSaleInvoice->sum('profit')),
                            'totaluomValue' => $totaluomValue,
                            'totalUnitQuantity' => $totalUnitQuantity,
                        ],
                    ],
                    'getAllSaleInvoice' => $converted,
                    'totalSaleInvoice' => $counted,
                ], 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());

                // Create cache key based on all parameters
                $cacheKey = 'sale_invoice_paginated_' . md5(serialize([
                    'page' => $request->query('page', 1),
                    'count' => $request->query('count', 10),
                    'salePersonId' => $request->query('salePersonId'),
                    'orderStatus' => $request->query('orderStatus'),
                    'customerId' => $request->query('customerId'),
                    'startDate' => $request->query('startDate'),
                    'endDate' => $request->query('endDate'),
                ]));

                $result = Cache::remember($cacheKey, 300, function () use ($request, $pagination) {
                    // Optimized query with minimal relationships
                    $allOrder = SaleInvoice::select('id', 'userId', 'customerId', 'totalAmount', 'paidAmount', 'dueAmount', 'profit', 'orderStatus', 'date', 'created_at')
                        ->with([
                            'user:id,firstName,lastName,username',
                            'customer:id,username'
                        ])
                        ->where('isHold', 'false')
                        ->orderBy('created_at', 'desc')
                        ->when($request->query('salePersonId'), function ($query) use ($request) {
                            return $query->whereIn('userId', explode(',', $request->query('salePersonId')));
                        })
                        ->when($request->query('orderStatus'), function ($query) use ($request) {
                            return $query->whereIn('orderStatus', explode(',', $request->query('orderStatus')));
                        })
                        ->when($request->query('customerId'), function ($query) use ($request) {
                            return $query->whereIn('customerId', explode(',', $request->query('customerId')));
                        })
                        ->when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                            return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                                ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                        })
                        ->skip($pagination['skip'])
                        ->take($pagination['limit'])
                        ->get();

                    $saleInvoicesIds = $allOrder->pluck('id')->toArray();

                    if (empty($saleInvoicesIds)) {
                        return [
                            'aggregations' => [
                                '_count' => ['id' => 0],
                                '_sum' => [
                                    'totalAmount' => 0,
                                    'paidAmount' => 0,
                                    'dueAmount' => 0,
                                    'totalReturnAmount' => 0,
                                    'instantPaidReturnAmount' => 0,
                                    'profit' => 0,
                                    'totaluomValue' => 0,
                                    'totalUnitQuantity' => 0,
                                ],
                            ],
                            'getAllSaleInvoice' => [],
                            'totalSaleInvoice' => 0,
                        ];
                    }

                    // Single optimized query for all transaction data
                    $transactionData = DB::select("
                        SELECT
                            relatedId,
                            SUM(CASE WHEN type = 'sale' AND debitId = 4 AND creditId = 8 THEN amount ELSE 0 END) as totalAmount,
                            SUM(CASE WHEN type = 'sale' AND creditId = 4 THEN amount ELSE 0 END) as paidAmount,
                            SUM(CASE WHEN type = 'sale_return' AND creditId = 4 THEN amount ELSE 0 END) as returnAmount,
                            SUM(CASE WHEN type = 'sale_return' AND debitId = 4 THEN amount ELSE 0 END) as instantReturnAmount
                        FROM transaction
                        WHERE relatedId IN (" . implode(',', $saleInvoicesIds) . ")
                        AND (
                            (type = 'sale' AND (debitId = 4 OR creditId = 4))
                            OR (type = 'sale_return' AND (creditId = 4 OR debitId = 4))
                        )
                        GROUP BY relatedId
                    ");

                    // Convert to associative array for fast lookup
                    $transactionLookup = [];
                    foreach ($transactionData as $trans) {
                        $transactionLookup[$trans->relatedId] = $trans;
                    }

                    // Fast calculation using lookup table
                    $allSaleInvoice = $allOrder->map(function ($item) use ($transactionLookup) {
                        $trans = $transactionLookup[$item->id] ?? null;

                        if ($trans) {
                            $totalAmount = $trans->totalAmount ?? 0;
                            $totalPaid = $trans->paidAmount ?? 0;
                            $totalReturnAmount = $trans->returnAmount ?? 0;
                            $instantPaidReturnAmount = $trans->instantReturnAmount ?? 0;

                            $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;

                            $item->paidAmount = takeUptoThreeDecimal($totalPaid);
                            $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                            $item->dueAmount = takeUptoThreeDecimal($totalDueAmount);
                            $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                        } else {
                            // Default values if no transactions found
                            $item->paidAmount = 0;
                            $item->instantPaidReturnAmount = 0;
                            $item->dueAmount = $item->totalAmount ?? 0;
                            $item->returnAmount = 0;
                        }

                        return $item;
                    });

                    $converted = arrayKeysToCamelCase($allSaleInvoice->toArray());

                    // Calculate aggregations from transaction data
                    $totalAmountSum = array_sum(array_column($transactionData, 'totalAmount'));
                    $paidAmountSum = array_sum(array_column($transactionData, 'paidAmount'));
                    $returnAmountSum = array_sum(array_column($transactionData, 'returnAmount'));
                    $instantReturnSum = array_sum(array_column($transactionData, 'instantReturnAmount'));
                    $totalDueAmount = (($totalAmountSum - $returnAmountSum) - $paidAmountSum) + $instantReturnSum;

                    // get total count of sale invoice (cached)
                    $counted = SaleInvoice::where('isHold', 'false')->count();

                    return [
                        'aggregations' => [
                            '_count' => [
                                'id' => $counted,
                            ],
                            '_sum' => [
                                'totalAmount' => takeUptoThreeDecimal($totalAmountSum),
                                'paidAmount' => takeUptoThreeDecimal($paidAmountSum),
                                'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                                'totalReturnAmount' => takeUptoThreeDecimal($returnAmountSum),
                                'instantPaidReturnAmount' => takeUptoThreeDecimal($instantReturnSum),
                                'profit' => takeUptoThreeDecimal($allSaleInvoice->sum('profit')),
                                'totaluomValue' => 0, // Simplified for performance
                                'totalUnitQuantity' => 0, // Simplified for performance
                            ],
                        ],
                        'getAllSaleInvoice' => $converted,
                        'totalSaleInvoice' => $counted,
                    ];
                });

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'invalid query!'], 400);
        }
    }

    // get a single saleInvoice controller method
    public function getSingleSaleInvoice($id): JsonResponse
    {
        try {
            // get single Sale invoice information with products
            $singleSaleInvoice = SaleInvoice::where('id', $id)
                ->with(['saleInvoiceProduct', 'saleInvoiceProduct' => function ($query) {
                    $query->with('product')->orderBy('id', 'desc');
                }, 'customer:id,username,address,phone,email', 'user:id,firstName,lastName,username'])
                ->where('isHold', 'false')
                ->first();

            if (!$singleSaleInvoice) {
                return response()->json(['error' => 'This invoice not Found'], 400);
            }


            // transaction of the total amount
            $totalAmount = Transaction::where('type', 'sale')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('debitId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the paidAmount
            $totalPaidAmount = Transaction::where('type', 'sale')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->orWhere('creditId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the total amount
            $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('creditId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // transaction of the total instant return
            $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                ->where('relatedId', $id)
                ->where(function ($query) {
                    $query->where('debitId', 4);
                })
                ->with('debit:id,name', 'credit:id,name')
                ->get();

            // calculation of due amount
            $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

            // get all transactions related to this sale invoice
            $transactions = Transaction::where('relatedId', $id)
                ->where(function ($query) {
                    $query->orWhere('type', 'sale')
                        ->orWhere('type', 'sale_return');
                })
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            // get totalReturnAmount of saleInvoice
            $returnSaleInvoice = ReturnSaleInvoice::where('saleInvoiceId', $id)
                ->with('returnSaleInvoiceProduct', 'returnSaleInvoiceProduct.product')
                ->orderBy('id', 'desc')
                ->get();

            $status = 'UNPAID';
            if ($totalDueAmount <= 0.0) {
                $status = "PAID";
            }

            // calculate total uomValue
            $totaluomValue = $singleSaleInvoice->saleInvoiceProduct->reduce(function ($acc, $item) {
                return $acc + (int)$item->product->uomValue * $item->productQuantity;
            }, 0);


            $convertedSingleSaleInvoice = arrayKeysToCamelCase($singleSaleInvoice->toArray());
            $convertedReturnSaleInvoice = arrayKeysToCamelCase($returnSaleInvoice->toArray());
            $convertedTransactions = arrayKeysToCamelCase($transactions->toArray());

            $finalResult = [
                'status' => $status,
                'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                'totalPaidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                'totaluomValue' => $totaluomValue,
                'singleSaleInvoice' => $convertedSingleSaleInvoice,
                'returnSaleInvoice' => $convertedReturnSaleInvoice,
                'transactions' => $convertedTransactions,
            ];

            return response()->json($finalResult, 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // update saleInvoice controller method
    public function updateSaleStatus(Request $request): JsonResponse
    {
        try {

            $saleInvoice = SaleInvoice::where('id', $request->input('invoiceId'))->first();

            if (!$saleInvoice) {
                return response()->json(['error' => 'SaleInvoice not Found!'], 404);
            }

            $saleInvoice->update([
                'orderStatus' => $request->input('orderStatus'),
            ]);

            return response()->json(['message' => 'Sale Invoice updated successfully!'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    // get all the saleInvoice controller method
    public function getAllSaleInvoiceByCustomer(Request $request): JsonResponse
    {

        $data = $request->attributes->get('data');
        
        if($data['role'] != 'customer' || !$data['sub']) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        

           if ($request->query()) {
              try {

                $customerId = $data['sub'];
                  $pagination = getPagination($request->query());
  
                  $allOrder = SaleInvoice::with('saleInvoiceProduct.product', 'customer:id,username')
                  ->where('customerId', $customerId)
                      ->where('isHold', 'false')
                      ->orderBy('created_at', 'desc')
                      ->when($request->query('orderStatus'), function ($query) use ($request) {
                          return $query->whereIn('orderStatus', explode(',', $request->query('orderStatus')));
                      })
                      ->skip($pagination['skip'])
                      ->take($pagination['limit'])
                      ->get();

                $totalOrder = SaleInvoice::with('saleInvoiceProduct', 'customer:id,username')
                ->where('customerId', $customerId)
                    ->where('isHold', 'false')
                    ->orderBy('created_at', 'desc')
                    ->when($request->query('orderStatus'), function ($query) use ($request) {
                        return $query->whereIn('orderStatus', explode(',', $request->query('orderStatus')));
                    })
                    ->count();
  
                  $saleInvoicesIds = $allOrder->pluck('id')->toArray();
                  // modify data to actual data of sale invoice's current value by adjusting with transactions and returns
                  $totalAmount = Transaction::where('type', 'sale')
                      ->whereIn('relatedId', $saleInvoicesIds)
                      ->where(function ($query) {
                          $query->where('debitId', 4)
                              ->where('creditId', 8);
                      })
                      ->get();
  
                  // transaction of the paidAmount
                  $totalPaidAmount = Transaction::where('type', 'sale')
                      ->whereIn('relatedId', $saleInvoicesIds)
                      ->where(function ($query) {
                          $query->orWhere('creditId', 4);
                      })
                      ->get();
  
                  // transaction of the total amount
                  $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                      ->whereIn('relatedId', $saleInvoicesIds)
                      ->where(function ($query) {
                          $query->where('creditId', 4);
                      })
                      ->get();
  
                  // transaction of the total instant return
                  $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                      ->whereIn('relatedId', $saleInvoicesIds)
                      ->where(function ($query) {
                          $query->where('debitId', 4);
                      })
                      ->get();
  
                  // calculate paid amount and due amount of individual sale invoice from transactions and returnSaleInvoice and attach it to saleInvoices
                  $allSaleInvoice = $allOrder->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {
  
                      $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                          return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->debitId === 4);
                      })->reduce(function ($acc, $current) {
                          return $acc + $current->amount;
                      }, 0);
  
                      $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                          return ($trans->relatedId === $item->id && $trans->type === 'sale' && $trans->creditId === 4);
                      })->reduce(function ($acc, $current) {
                          return $acc + $current->amount;
                      }, 0);
  
                      $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                          return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->creditId === 4);
                      })->reduce(function ($acc, $current) {
                          return $acc + $current->amount;
                      }, 0);
  
                      $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                          return ($trans->relatedId === $item->id && $trans->type === 'sale_return' && $trans->debitId === 4);
                      })->reduce(function ($acc, $current) {
                          return $acc + $current->amount;
                      }, 0);
  
                      $totalDueAmount = (($totalAmount - $totalReturnAmount) - $totalPaid) + $instantPaidReturnAmount;
  
  
                      $item->paidAmount = takeUptoThreeDecimal($totalPaid);
                      $item->instantPaidReturnAmount = takeUptoThreeDecimal($instantPaidReturnAmount);
                      $item->dueAmount = takeUptoThreeDecimal($totalDueAmount);
                      $item->returnAmount = takeUptoThreeDecimal($totalReturnAmount);
                      return $item;
                  });
  
                  $converted = arrayKeysToCamelCase($allSaleInvoice->toArray());

                  return response()->json([
                      'getAllSaleInvoice' => $converted,
                      'totalSaleInvoice' => $totalOrder,
                  ], 200);
              } catch (Exception $err) {
                  return response()->json(['error' => $err->getMessage()], 500);
              }
          } else {
              return response()->json(['error' => 'invalid query!'], 400);
          }
    }
      // get a single saleInvoice controller method
      public function getSingleSaleInvoiceForCustomer(Request $request, $id): JsonResponse
      {
          try {

            $data = $request->attributes->get('data');
        
            if($data['role'] != 'customer' || !$data['sub']) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
              // get single Sale invoice information with products
              $singleSaleInvoice = SaleInvoice::where('id', $id)
              ->where('customerId', $data['sub'])
                  ->with(['saleInvoiceProduct', 'saleInvoiceProduct' => function ($query) {
                      $query->with('product')->orderBy('id', 'desc');
                  }, 'customer:id,username,address,phone,email', 'user:id,firstName,lastName,username'])
                  ->where('isHold', 'false')
                  ->first();
  
              if (!$singleSaleInvoice) {
                  return response()->json(['error' => 'This invoice not Found'], 400);
              }
  
  
              // transaction of the total amount
              $totalAmount = Transaction::where('type', 'sale')
                  ->where('relatedId', $id)
                  ->where(function ($query) {
                      $query->where('debitId', 4);
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->get();
  
              // transaction of the paidAmount
              $totalPaidAmount = Transaction::where('type', 'sale')
                  ->where('relatedId', $id)
                  ->where(function ($query) {
                      $query->orWhere('creditId', 4);
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->get();
  
              // transaction of the total amount
              $totalAmountOfReturn = Transaction::where('type', 'sale_return')
                  ->where('relatedId', $id)
                  ->where(function ($query) {
                      $query->where('creditId', 4);
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->get();
  
              // transaction of the total instant return
              $totalInstantReturnAmount = Transaction::where('type', 'sale_return')
                  ->where('relatedId', $id)
                  ->where(function ($query) {
                      $query->where('debitId', 4);
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->get();
  
              // calculation of due amount
              $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');
  
              // get all transactions related to this sale invoice
              $transactions = Transaction::where('relatedId', $id)
                  ->where(function ($query) {
                      $query->orWhere('type', 'sale')
                          ->orWhere('type', 'sale_return');
                  })
                  ->with('debit:id,name', 'credit:id,name')
                  ->orderBy('id', 'desc')
                  ->get();
  
              // get totalReturnAmount of saleInvoice
              $returnSaleInvoice = ReturnSaleInvoice::where('saleInvoiceId', $id)
                  ->with('returnSaleInvoiceProduct', 'returnSaleInvoiceProduct.product')
                  ->orderBy('id', 'desc')
                  ->get();
  
              $status = 'UNPAID';
              if ($totalDueAmount <= 0.0) {
                  $status = "PAID";
              }
  
              // calculate total uomValue
              $totaluomValue = $singleSaleInvoice->saleInvoiceProduct->reduce(function ($acc, $item) {
                  return $acc + (int)$item->product->uomValue * $item->productQuantity;
              }, 0);
  
  
              $convertedSingleSaleInvoice = arrayKeysToCamelCase($singleSaleInvoice->toArray());
              $convertedReturnSaleInvoice = arrayKeysToCamelCase($returnSaleInvoice->toArray());
              $convertedTransactions = arrayKeysToCamelCase($transactions->toArray());
  
              $finalResult = [
                  'status' => $status,
                  'totalAmount' => takeUptoThreeDecimal($totalAmount->sum('amount')),
                  'totalPaidAmount' => takeUptoThreeDecimal($totalPaidAmount->sum('amount')),
                  'totalReturnAmount' => takeUptoThreeDecimal($totalAmountOfReturn->sum('amount')),
                  'instantPaidReturnAmount' => takeUptoThreeDecimal($totalInstantReturnAmount->sum('amount')),
                  'dueAmount' => takeUptoThreeDecimal($totalDueAmount),
                  'totaluomValue' => $totaluomValue,
                  'singleSaleInvoice' => $convertedSingleSaleInvoice,
                  'returnSaleInvoice' => $convertedReturnSaleInvoice,
                  'transactions' => $convertedTransactions,
              ];
  
              return response()->json($finalResult, 200);
          } catch (Exception $err) {
              return response()->json(['error' => $err->getMessage()], 500);
          }
      }

    /**
     * Fast API for sale invoices with minimal data and aggressive caching
     */
    public function getAllSaleInvoiceFast(Request $request): JsonResponse
    {
        try {
            if ($request->query()) {
                $pagination = getPagination($request->query());

                // Create cache key based on parameters
                $cacheKey = 'sale_invoice_fast_' . md5(serialize([
                    'page' => $request->query('page', 1),
                    'count' => $request->query('count', 10),
                    'status' => $request->query('status'),
                    'startDate' => $request->query('startDate'),
                    'endDate' => $request->query('endDate'),
                ]));

                $result = Cache::remember($cacheKey, 600, function () use ($request, $pagination) { // 10 minutes cache
                    $query = SaleInvoice::select('id', 'customerId', 'userId', 'totalAmount', 'paidAmount', 'dueAmount', 'orderStatus', 'date', 'created_at')
                        ->with([
                            'customer:id,username',
                            'user:id,firstName,lastName'
                        ])
                        ->where('isHold', 'false')
                        ->orderBy('created_at', 'desc');

                    // Apply filters
                    if ($request->query('status')) {
                        $query->where('status', $request->query('status'));
                    }

                    if ($request->query('startDate') && $request->query('endDate')) {
                        $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate'))->startOfDay())
                              ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate'))->endOfDay());
                    }

                    $invoices = $query->skip($pagination['skip'])
                                     ->take($pagination['limit'])
                                     ->get();

                    $total = $query->count();

                    return [
                        'invoices' => arrayKeysToCamelCase($invoices->toArray()),
                        'total' => $total,
                        'page' => $request->query('page', 1),
                        'count' => $request->query('count', 10),
                        'cached' => true,
                        'fast' => true
                    ];
                });

                return response()->json($result, 200);
            }

            return response()->json(['error' => 'Query parameters required'], 400);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred: ' . $err->getMessage()], 500);
        }
    }
}
