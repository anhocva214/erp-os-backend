<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\ReturnPurchaseInvoice;
use App\Models\Supplier;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SupplierController extends Controller
{
    //create supplier controller method
    public function createSingleSupplier(Request $request): JsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedSupplier = Supplier::destroy($ids);

                $deletedCount = [
                    'count' => $deletedSupplier
                ];

                return response()->json($deletedCount, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during delete supplier. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'createmany') {
            try {
                $supplierData = json_decode($request->getContent(), true);

                $supplier = Supplier::whereIn('phone', array_column($supplierData, 'phone'))->get();
                if ($supplier->count() > 0) {
                    $phone = $supplier->map(function ($item) {
                        return $item->phone;
                    });
                    //beautify the phone number
                    $phone = implode(', ', $phone->toArray());
                    return response()->json(['error' => $phone . ' Phone Number Exist, Try Another Phone Number!'], 500);
                }

                //check if name already exists
                $supplierData = collect($supplierData)->map(function ($item) {
                    $supplier = Supplier::where('name', $item['name'])->first();
                    if ($supplier) {
                        return null;
                    }
                    return $item;
                })->filter(function ($item) {
                    return $item !== null;
                })->toArray();

                //if all products already exists
                if (count($supplierData) === 0) {
                    return response()->json(['error' => 'All Supplier already exists.'], 500);
                }

                $createdSupplier = collect($supplierData)->map(function ($item) {
                    return Supplier::firstOrCreate($item);
                });

                $result = [
                    'count' => count($createdSupplier),
                ];

                return response()->json($result, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create supplier. Please try again later.'], 500);
            }
        } else {
            try {
                $supplierData = json_decode($request->getContent(), true);

                $supplier = Supplier::where('phone', $supplierData['phone'])->first();
                if ($supplier) {
                    return response()->json(['error' => 'Phone Number Exist, Try Another Phone Number!'], 500);
                }

                $createdSupplier = Supplier::create([
                    'name' => $supplierData['name'],
                    'phone' => $supplierData['phone'],
                    'address' => $supplierData['address'] ?? null,
                    'email' => $supplierData['email'] ?? null,
                ]);

                $converted = arrayKeysToCamelCase($createdSupplier->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create supplier. Please try again later.'], 500);
            }
        }
    }

    // get all the supplier controller method
    public function getAllSupplier(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                // Optimized query with caching and minimal data
                $cacheKey = 'suppliers_all_optimized';
                $allSupplier = Cache::remember($cacheKey, 900, function () { // 15 minutes cache
                    return Supplier::orderBy('id', 'desc')
                        ->select('id', 'name', 'phone', 'email', 'address', 'status')
                        ->where('status', 'true')
                        ->get();
                });

                $converted = arrayKeysToCamelCase($allSupplier->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting supplier. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'info') {
            try {
                $aggregation = Supplier::where('status', 'true')
                    ->count();

                $result = [
                    '_count' => [
                        'id' => $aggregation,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting supplier. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());

                $key = trim($request->query('key'));

                $getAllSupplier = Supplier::where(function ($query) use ($key) {
                    return $query->orWhere('name', 'LIKE', '%' . $key . '%')
                        ->orWhere('phone', 'LIKE', '%' . $key . '%')
                        ->orWhere('address', 'LIKE', '%' . $key . '%');
                })
                    ->orderBy('id', 'desc')
                    ->with('purchaseInvoice')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $supplierCount = Supplier::where(function ($query) use ($key) {
                    return $query->orWhere('name', 'LIKE', '%' . $key . '%')
                        ->orWhere('phone', 'LIKE', '%' . $key . '%')
                        ->orWhere('address', 'LIKE', '%' . $key . '%');
                })
                    ->count();

                $converted = arrayKeysToCamelCase($getAllSupplier->toArray());
                $finalResult = [
                    'getAllSupplier' => $converted,
                    'totalSupplier' => $supplierCount,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting customer. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'report') {
            try {
                $allSupplier = Supplier::orderBy('id', 'desc')
                    ->with('purchaseInvoice')
                    ->get();

                //with total purchase amount, total paid amount, total due amount
                $allSupplier = $allSupplier->map(function ($item) {
                    $allPurchaseInvoiceId = $item->purchaseInvoice->map(function ($item) {
                        return $item->id;
                    });

                    // transaction of the total amount
                    $totalAmount = Transaction::where('type', 'purchase')
                        ->whereIn('relatedId', $allPurchaseInvoiceId)
                        ->where(function ($query) {
                            $query->where('creditId', 5);
                        })
                        ->get();

                    // transaction of the paidAmount
                    $totalPaidAmount = Transaction::where('type', 'purchase')
                        ->whereIn('relatedId', $allPurchaseInvoiceId)
                        ->where(function ($query) {
                            $query->orWhere('debitId', 5);
                        })
                        ->get();

                    // transaction of the total amount
                    $totalAmountOfReturn = Transaction::where('type', 'purchase_return')
                        ->whereIn('relatedId', $allPurchaseInvoiceId)
                        ->where(function ($query) {
                            $query->where('debitId', 5);
                        })
                        ->get();

                    // transaction of the total instant return
                    $totalInstantReturnAmount = Transaction::where('type', 'purchase_return')
                        ->whereIn('relatedId', $allPurchaseInvoiceId)
                        ->where(function ($query) {
                            $query->where('creditId', 5);
                        })
                        ->get();

                    // calculate grand total due amount
                    $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');

                    $totalAmount = $totalAmount->sum('amount');
                    $totalPaidAmount = $totalPaidAmount->sum('amount');
                    $totalReturnAmount = $totalAmountOfReturn->sum('amount');
                    $instantPaidReturnAmount = $totalInstantReturnAmount->sum('amount');
                    $dueAmount = $totalDueAmount;

                    // include dueAmount in singleSupplier
                    $item->totalAmount = takeUptoThreeDecimal((float)$totalAmount) ?? 0;
                    $item->totalPaidAmount = takeUptoThreeDecimal((float)$totalPaidAmount) ?? 0;
                    $item->totalReturnAmount = takeUptoThreeDecimal((float)$totalReturnAmount) ?? 0;
                    $item->instantPaidReturnAmount = takeUptoThreeDecimal((float)$instantPaidReturnAmount) ?? 0;
                    $item->dueAmount = takeUptoThreeDecimal((float)$dueAmount) ?? 0;

                    return $item;
                });

                $grandData = [
                    'grandTotalAmount' => $allSupplier->sum('totalAmount'),
                    'grandTotalPaidAmount' => $allSupplier->sum('totalPaidAmount'),
                    'grandTotalReturnAmount' => $allSupplier->sum('totalReturnAmount'),
                    'grandInstantPaidReturnAmount' => $allSupplier->sum('instantPaidReturnAmount'),
                    'grandDueAmount' => $allSupplier->sum('dueAmount'),
                ];

                $converted = arrayKeysToCamelCase($allSupplier->toArray());

                $finalResult = [
                    'grandData' => $grandData,
                    'allSupplier' => $converted,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting supplier. Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $allSupplier = Supplier::when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })
                    ->orderBy('id', 'desc')
                    ->with('purchaseInvoice')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $allSupplierCount = Supplier::when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })
                    ->count();

                $converted = arrayKeysToCamelCase($allSupplier->toArray());
                $finalResult = [
                    'getAllSupplier' => $converted,
                    'totalSupplier' => $allSupplierCount,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting supplier. Please try again later.'], 500);
            }
        } else {
            try {
                $pagination = getPagination($request->query());
                $allSupplier = Supplier::orderBy('id', 'desc')
                    ->where('status', 'true')
                    ->with('purchaseInvoice')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allSupplier->toArray());
                $finalResult = [
                    'getAllSupplier' => $converted,
                    'totalSupplier' => Supplier::where('status', 'true')->count(),
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting supplier. Please try again later.'], 500);
            }
        }
    }

    // get a single supplier controller method
    public function getSingleSupplier(Request $request, $id): JsonResponse
    {
        try {

            // all invoice of a supplier with return purchase invoice nested
            $singleSupplier = Supplier::where('id', (int)$id)
                ->with('purchaseInvoice.purchaseInvoiceProduct')
                ->first();

            // all invoice of a supplier with return purchase invoice nested
            $suppliersAllInvoice = Supplier::where('id', (int)$id)
                ->with(['purchaseInvoice' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }])
                ->first();

            // get all purchaseInvoice of a purchaser
            $allPurchaseInvoiceId = $suppliersAllInvoice->purchaseInvoice->map(function ($item) {
                return $item->id;
            });

            // get all returnPurchaseInvoice of a purchaser
            $allReturnPurchaseInvoiceId = $suppliersAllInvoice->purchaseInvoice->flatMap(function ($item) {
                return !empty($item->returnPurchaseInvoice) ? $item->returnPurchaseInvoice : [];
            });

            // transaction of the total amount
            $totalAmount = Transaction::where('type', 'purchase')
                ->whereIn('relatedId', $allPurchaseInvoiceId)
                ->where(function ($query) {
                    $query->where('creditId', 5);
                })
                ->get();

            // transaction of the paidAmount
            $totalPaidAmount = Transaction::where('type', 'purchase')
                ->whereIn('relatedId', $allPurchaseInvoiceId)
                ->where(function ($query) {
                    $query->orWhere('debitId', 5);
                })
                ->get();

            // transaction of the total amount
            $totalAmountOfReturn = Transaction::where('type', 'purchase_return')
                ->whereIn('relatedId', $allPurchaseInvoiceId)
                ->where(function ($query) {
                    $query->where('debitId', 5);
                })
                ->get();

            // transaction of the total instant return
            $totalInstantReturnAmount = Transaction::where('type', 'purchase_return')
                ->whereIn('relatedId', $allPurchaseInvoiceId)
                ->where(function ($query) {
                    $query->where('creditId', 5);
                })
                ->get();

            //get all transactions related to purchaseInvoiceId
            $allTransaction = Transaction::whereIn('type', ["purchase", "purchase_return"])
                ->whereIn('relatedId', $allPurchaseInvoiceId)
                ->with('debit:id,name', 'credit:id,name')
                ->orderBy('id', 'desc')
                ->get();

            //get all return purchase Invoice
            $allReturnPurchaseInvoice = ReturnPurchaseInvoice::whereIn('purchaseInvoiceId', $allPurchaseInvoiceId)
                ->orderBy('created_at', 'desc')
                ->with('returnPurchaseInvoiceProduct')
                ->get();

            // calculate grand total due amount
            $totalDueAmount = (($totalAmount->sum('amount') - $totalAmountOfReturn->sum('amount')) - $totalPaidAmount->sum('amount')) + $totalInstantReturnAmount->sum('amount');


            // include dynamic transaction data in singleSupplier
            $singleSupplier->totalAmount = takeUptoThreeDecimal((float)$totalAmount->sum('amount')) ?? 0;
            $singleSupplier->totalPaidAmount = takeUptoThreeDecimal((float)$totalPaidAmount->sum('amount')) ?? 0;
            $singleSupplier->totalReturnAmount = takeUptoThreeDecimal((float)$totalAmountOfReturn->sum('amount')) ?? 0;
            $singleSupplier->instantPaidReturnAmount = takeUptoThreeDecimal((float)$totalInstantReturnAmount->sum('amount')) ?? 0;
            $singleSupplier->dueAmount = takeUptoThreeDecimal((float)$totalDueAmount) ?? 0;
            $singleSupplier->totalPurchaseInvoice = $allPurchaseInvoiceId->count() ?? 0;
            $singleSupplier->totalReturnPurchaseInvoice = $allReturnPurchaseInvoiceId->count() ?? 0;
            $singleSupplier->allTransaction = arrayKeysToCamelCase($allTransaction->toArray());
            $singleSupplier->returnPurchaseInvoice = arrayKeysToCamelCase($allReturnPurchaseInvoice->toArray());



            // ===  modify each purchase invoice of supplier with dynamic transaction data === //
            $singleSupplier->purchaseInvoice->map(function ($item) use ($totalAmount, $totalPaidAmount, $totalAmountOfReturn, $totalInstantReturnAmount) {

                $totalAmount = $totalAmount->filter(function ($trans) use ($item) {
                    return ($trans->relatedId === $item->id && $trans->type === 'purchase' && $trans->creditId === 5);
                })->reduce(function ($acc, $current) {
                    return $acc + $current->amount;
                }, 0);

                $totalPaid = $totalPaidAmount->filter(function ($trans) use ($item) {
                    return ($trans->relatedId === $item->id && $trans->type === 'purchase' && $trans->debitId === 5);
                })->reduce(function ($acc, $current) {
                    return $acc + $current->amount;
                }, 0);

                $totalReturnAmount = $totalAmountOfReturn->filter(function ($trans) use ($item) {
                    return ($trans->relatedId === $item->id && $trans->type === 'purchase_return' && $trans->debitId === 5);
                })->reduce(function ($acc, $current) {
                    return $acc + $current->amount;
                }, 0);

                $instantPaidReturnAmount = $totalInstantReturnAmount->filter(function ($trans) use ($item) {
                    return ($trans->relatedId === $item->id && $trans->type === 'purchase_return' && $trans->creditId === 5);
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

            $converted = arrayKeysToCamelCase($singleSupplier->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting supplier. Please try again later.', $err->getMessage()], 500);
        }
    }

    // update single supplier controller method
    public function updateSingleSupplier(Request $request, $id): JsonResponse
    {
        try {
            $updatedSupplier = Supplier::where('id', (int)$id)->first();
            $updatedSupplier->update([
                'name' => $request->input('name') ? $request->input('name') : $updatedSupplier->name,
                'phone' => $request->input('phone') ? $request->input('phone') : $updatedSupplier->phone,
                'address' => $request->input('address') ? $request->input('address') : $updatedSupplier->address,
                'email' => $request->input('email') ? $request->input('email') : $updatedSupplier->email,
            ]);

            if (!$updatedSupplier) {
                return response()->json(['error' => 'Failed To Update Supplier'], 404);
            }
            return response()->json(['message' => 'Supplier updated Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during update supplier. Please try again later.'], 500);
        }
    }

    // delete single supplier controller method
    public function deleteSingleSupplier(Request $request, $id): JsonResponse
    {
        try {
            $deletedSupplier = Supplier::where('id', (int)$id)
                ->update([
                    'status' => $request->input('status')
                ]);

            if (!$deletedSupplier) {
                return response()->json(['error' => 'Failed To Hide Supplier'], 404);
            }
            return response()->json(['message' => 'Supplier Hided Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete supplier. Please try again later.'], 500);
        }
    }
}
