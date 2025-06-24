<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\SaleInvoice;
use App\Models\Transaction;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

//
class TransactionController extends Controller
{
    public function createTransaction(Request $request): JsonResponse
    {
        DB::beginTransaction();
        $request->validate([
            'date' => 'required',
            'particulars' => 'required',
            'debitId' => 'required',
            'creditId' => 'required',
            'amount' => 'required',
        ]);
        try {
            $date = new DateTime($request->input('date'));
            $formattedDate = $date->format('Y-m-d H:i:s'); // Adjust the format according to your needs
            $request->merge([
                'date' => $formattedDate,
            ]);

            if ($request->input('type') === 'purchase' || $request->input('type') === 'purchase_return') {
                $purchaseInvoice = PurchaseInvoice::where('id', $request->input('relatedId'));
                if (!$purchaseInvoice) {
                    return response()->json(['error' => 'related Id not found!'], 400);
                }
            }

            if ($request->input('type') === 'sale' || $request->input('type') === 'sale_return') {
                $saleInvoice = SaleInvoice::where('id', $request->input('relatedId'));
                if (!$saleInvoice) {
                    return response()->json(['error' => 'related Id not found!'], 400);
                }
            }

            $transaction = Transaction::create($request->all());
            DB::commit();
            return response()->json($transaction, 201);
        } catch (Exception $err) {
            DB::rollBack();
            return response()->json(['error' => 'An error occurred during create transaction. Please try again later.'], 500);
        }
    }

    public function getAllTransaction(Request $request): JsonResponse
    {
        if ($request->query('query') === 'info') {
            try {
                $aggregations = Transaction::where('status', "true")
                    ->selectRaw('COUNT(id) as _count, SUM(amount) as _sum')
                    ->first();

                $response = [
                    '_count' => [
                        'id' => $aggregations->_count ?? 0,
                    ],
                    '_sum' => [
                        'amount' => $aggregations->_sum ?? null,
                    ],
                ];
                return response()->json($response, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting transaction. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'all') {
            try {
                $allTransaction = Transaction::with('debit', 'credit')->orderBy('id', 'desc')->get();
                $converted = arrayKeysToCamelCase($allTransaction->toArray());
                return response()->json($converted, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting transaction. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'inactive') {
            try {
                $aggregations = Transaction::query()
                    ->selectRaw('COUNT(id) as totalCount, SUM(amount) as totalAmount')
                    ->where('date', '>=', $request->query('startDate'))
                    ->where('date', '<=', $request->query('endDate'))
                    ->where('status', "false")
                    ->first();

                $allTransaction = Transaction::query()
                    ->where('date', '>=', $request->query('startDate'))
                    ->where('date', '<=', $request->query('endDate'))
                    ->where('status', "false")
                    ->orderBy('id', 'desc')
                    ->with('debit', 'credit')
                    ->get();

                $converted = arrayKeysToCamelCase($allTransaction->toArray());

                $response = [
                    'aggregations' => [
                        '_count' => [
                            'id' => $aggregations->totalCount ?? 0,
                        ],
                        '_sum' => [
                            'amount' => $aggregations->totalAmount ?? null,
                        ],
                    ],
                    'allTransaction' => $converted,
                ];

                return response()->json($response, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting transaction. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());
                $key = trim($request->query('key'));

                $allTransaction = Transaction::where('id', 'LIKE', '%' . $key . '%')
                    ->orWhere('type', 'LIKE', '%' . $key . '%')
                    ->orWhereHas('debit', function ($query) use ($key) {
                        $query->where('name', 'LIKE', '%' . $key . '%');
                    })
                    ->orWhereHas('credit', function ($query) use ($key) {
                        $query->where('name', 'LIKE', '%' . $key . '%');
                    })
                    ->with('debit', 'credit')
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $allTransactionCount = Transaction::where('id', 'LIKE', '%' . $key . '%')
                    ->count();

                $converted = arrayKeysToCamelCase($allTransaction->toArray());
                $finalResult = [
                    'getAllTransaction' => $converted,
                    'totalTransaction' => $allTransactionCount,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting transaction. Please try again later.'], 500);
            }
        } else {
            $pagination = getPagination($request->query());
            try {
                $aggregations = Transaction::query()
                    ->where('date', '>=', $request->query('startDate'))
                    ->where('date', '<=', $request->query('endDate'))
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->when($request->query('debitId'), function ($query) use ($request) {
                        return $query->whereIn('debitId', explode(',', $request->query('debitId')));
                    })
                    ->when($request->query('creditId'), function ($query) use ($request) {
                        return $query->whereIn('creditId', explode(',', $request->query('creditId')));
                    })
                    ->selectRaw('COUNT(id) as totalCount, SUM(amount) as totalAmount')
                    ->get();


                $allTransaction = Transaction::query()
                    ->where('date', '>=', $request->query('startDate'))
                    ->where('date', '<=', $request->query('endDate'))
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->when($request->query('debitId'), function ($query) use ($request) {
                        return $query->whereIn('debitId', explode(',', $request->query('debitId')));
                    })
                    ->when($request->query('creditId'), function ($query) use ($request) {
                        return $query->whereIn('creditId', explode(',', $request->query('creditId')));
                    })
                    ->orderBy('id', 'desc')
                    ->with('debit', 'credit')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allTransaction->toArray());

                $response = [
                    'aggregations' => [
                        '_count' => [
                            'id' => $aggregations[0]->totalCount ?? 0,
                        ],
                        '_sum' => [
                            'amount' => $aggregations[0]->totalAmount ?? null,
                        ],
                    ],
                    'getAllTransaction' => $converted,
                    'totalTransaction' => $aggregations[0]->totalCount ?? 0,
                ];

                return response()->json($response, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting transaction. Please try again later.'], 500);
            }
        }
    }

    // get a single transaction controller method
    public function getSingleTransaction(Request $request, $id): JsonResponse
    {
        try {
            $singleTransaction = Transaction::where('id', (int)$id)
                ->with('debit:id,name', 'credit:id,name')
                ->first();
            $converted = arrayKeysToCamelCase($singleTransaction->toArray());
            return response()->json($converted, 200);
        } catch (Exception) {
            return response()->json(['error' => 'An error occurred during getting transaction. Please try again later.'], 500);
        }
    }

    // update a single transaction controller method
    public function updateSingleTransaction(Request $request, $id): JsonResponse
    {
        try {
            $date = Carbon::parse($request->input('date'));

            $updatedTransaction = Transaction::where('id', (int)$id)->update([
                'date' => $date,
                'particulars' => $request->input('particulars'),
                'type' => 'transaction',
                'relatedId' => 0,
                'amount' => takeUptoThreeDecimal((float)$request->input('amount')),
            ]);

            if (!$updatedTransaction) {
                return response()->json(['error' => 'Failed To Update Transaction'], 404);
            }
            return response()->json(['message' => 'Transaction updated successfully'], 200);
        } catch (Exception $error) {
            return response()->json(['error' => 'An error occurred during update transaction. Please try again later.'], 500);
        }
    }

    // delete a single transaction controller method
    public function deleteSingleTransaction(Request $request, $id): JsonResponse
    {
        try {
            $deletedTransaction = Transaction::where('id', (int)$id)->update([
                'status' => $request->input('status'),
            ]);

            if (!$deletedTransaction) {
                return response()->json(['error' => 'Failed To Update Transaction'], 404);
            }
            return response()->json(['message' => 'Transaction deleted successfully'], 200);
        } catch (Exception $error) {
            return response()->json(['error' => 'An error occurred during delete transaction. Please try again later.'], 500);
        }
    }
}
