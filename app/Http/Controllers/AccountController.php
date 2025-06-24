<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\SubAccount;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    //create subAccount
    public function createSubAccount(Request $request): JsonResponse
    {
        try {
            $createdSubAccount = SubAccount::create([
                'name' => $request->input('name'),
                'accountId' => $request->input('accountId'),
            ]);
            $converted = arrayKeysToCamelCase($createdSubAccount->toArray());
            return response()->json($converted, 201);
        } catch (Exception $error) {
            return response()->json(['error' => 'An error occurred during create subAccount. Please try again later.'], 500);
        }
    }

    //get all account
    public function getAllAccount(Request $request): JsonResponse
    {
        if ($request->query('query') === 'tb') {
            try {
                $allAccounts = Account::orderBy('id', 'desc')
                    ->with(['subAccount' => function ($query) {
                        $query->with(['debit' => function ($query) {
                            $query->where('status', 'true');
                            $query->orderBy('id', 'desc');
                        }, 'credit' => function ($query) {
                            $query->where('status', 'true');
                            $query->orderBy('id', 'desc');
                        }]);
                    }])
                    ->get();

                $accountInfo = [];

                foreach ($allAccounts as $account) {
                    foreach ($account->subAccount as $subAccount) {
                        $totalDebit = $subAccount->debit->where('status', true)->sum('amount');
                        $totalCredit = $subAccount->credit->where('status', true)->sum('amount');
                        $balance = $totalDebit - $totalCredit;

                        $accountInfo[] = [
                            'account' => $account->name,
                            'subAccount' => $subAccount->name,
                            'totalDebit' => takeUptoThreeDecimal($totalDebit),
                            'totalCredit' => takeUptoThreeDecimal($totalCredit),
                            'balance' => takeUptoThreeDecimal($balance),
                        ];
                    }
                }

                $trialBalance = $accountInfo; // Assuming you already have $accountInfo

                $debits = [];
                $credits = [];

                foreach ($trialBalance as $item) {
                    if ($item['balance'] > 0) {
                        $debits[] = $item;
                    }
                    if ($item['balance'] < 0) {
                        $credits[] = $item;
                    }
                }

                // Assuming you have already separated items into $debits and $credits arrays

                $totalDebit = array_reduce($debits, function ($carry, $debit) {
                    return $carry + $debit['balance'];
                }, 0);

                $totalCredit = array_reduce($credits, function ($carry, $credit) {
                    return $carry + $credit['balance'];
                }, 0);

                $match = true;

                if (-$totalDebit === $totalCredit) {
                    $match = true;
                } else {
                    $match = false;
                }

                $responseData = [
                    'match' => $match,
                    'totalDebit' => $totalDebit,
                    'totalCredit' => $totalCredit,
                    'debits' => $debits,
                    'credits' => $credits,
                ];

                return response()->json($responseData)->setStatusCode(200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting trial balance. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'bs') {
            try {
                $allAccount = Account::orderBy('id', 'desc')
                    ->with('subAccount.credit', 'subAccount.debit')
                    ->get();


                $accountInfo = [];

                foreach ($allAccount as $account) {
                    foreach ($account->subAccount as $subAccount) {
                        $totalDebit = $subAccount->debit->sum('amount');
                        $totalCredit = $subAccount->credit->sum('amount');
                        $balance = $totalDebit - $totalCredit;


                        // Add the total debit and total credit to each subAccount object
                        $subAccount->totalDebit = $totalDebit;
                        $subAccount->totalCredit = $totalCredit;
                        $subAccount->balance = $balance;

                        // Create an array for the transformed subAccount data
                        $accountInfo[] = [
                            'account' => $account->type,
                            'subAccount' => $subAccount->name,
                            'totalDebit' => takeUptoThreeDecimal($totalDebit),
                            'totalCredit' => takeUptoThreeDecimal($totalCredit),
                            'balance' => takeUptoThreeDecimal($balance),
                        ];
                    }
                }

                $balanceSheet = $accountInfo;
                $assets = [];
                $liabilities = [];
                $equity = [];

                foreach ($balanceSheet as $item) {
                    if ($item['account'] === "Asset" && $item['balance'] !== 0) {
                        $assets[] = $item;
                    }
                    if ($item['account'] === "Liability" && $item['balance'] !== 0) {
                        // Convert negative balance to positive
                        $item['balance'] = -$item['balance'];
                        $liabilities[] = $item;
                    }
                    if ($item['account'] === "Equity" && $item['balance'] !== 0) {
                        // Convert negative balance to positive
                        $item['balance'] = -$item['balance'];
                        $equity[] = $item;
                    }
                }

                $totalAsset = array_reduce($assets, function ($carry, $asset) {
                    return $carry + $asset['balance'];
                }, 0);

                $totalLiability = array_reduce($liabilities, function ($carry, $liability) {
                    return $carry + $liability['balance'];
                }, 0);

                $totalEquity = array_reduce($equity, function ($carry, $equityItem) {
                    return $carry + $equityItem['balance'];
                }, 0);

                if (-$totalAsset === $totalLiability + $totalEquity) {
                    $match = true;
                } else {
                    $match = false;
                }

                $responseData = [
                    'match' => $match,
                    'totalAsset' => $totalAsset,
                    'totalLiability' => $totalLiability,
                    'totalEquity' => $totalEquity,
                    'assets' => $assets,
                    'liabilities' => $liabilities,
                    'equity' => $equity,
                ];

                return response()->json($responseData, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting balance sheet. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'is') {
            try {
                $allAccount = Account::with('subAccount.credit', 'subAccount.debit')
                    ->orderBy('id', 'desc')->get();

                $accountInfo = [];

                foreach ($allAccount as $account) {
                    foreach ($account->subAccount as $subAccount) {
                        $totalDebit = $subAccount->debit->sum('amount');
                        $totalCredit = $subAccount->credit->sum('amount');
                        $balance = $totalDebit - $totalCredit;

                        // Create an array for the transformed subAccount data
                        $accountInfo[] = [
                            'id' => $subAccount->id,
                            'account' => $account->name,
                            'subAccount' => $subAccount->name,
                            'totalDebit' => takeUptoThreeDecimal($totalDebit),
                            'totalCredit' => takeUptoThreeDecimal($totalCredit),
                            'balance' => takeUptoThreeDecimal($balance),
                        ];
                    }
                }

                $incomeStatement = $accountInfo;
                $revenue = [];
                $expense = [];

                foreach ($incomeStatement as $item) {
                    if ($item['account'] === "Revenue" && $item['balance'] !== 0) {
                        // Convert negative balance to positive
                        $item['balance'] = -$item['balance'];
                        $revenue[] = $item;
                    }
                    if ($item['account'] === "Expense" && $item['balance'] !== 0) {
                        // Convert negative balance to positive
                        $item['balance'] = -$item['balance'];
                        $expense[] = $item;
                    }
                }


                $totalRevenue = array_reduce($revenue, function ($carry, $revenueItem) {
                    return $carry + $revenueItem['balance'];
                }, 0);

                $totalExpense = array_reduce($expense, function ($carry, $expenseItem) {
                    return $carry + $expenseItem['balance'];
                }, 0);

                $profit = $totalRevenue + $totalExpense;

                $responseData = [
                    'totalRevenue' => $totalRevenue,
                    'totalExpense' => $totalExpense,
                    'profit' => $profit,
                    'revenue' => $revenue,
                    'expense' => $expense,
                ];

                return response()->json($responseData, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting income statement. Please try again later.'], 500);
            }
        } elseif ($request->query('type') === 'sa' && $request->query('query') === 'all') {
            try {
                $allSubAccount = SubAccount::where('status', 'true')
                    ->with(['account' => function ($query) {
                        $query->orderBy('id', 'desc');
                    }])
                    ->orderBy('id', 'desc')
                    ->get();

                $converted = arrayKeysToCamelCase($allSubAccount->toArray());

                return response()->json($converted, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting sub account. Please try again later.'], 500);
            }
        } elseif ($request->query('type') === 'sa' && $request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());
                $key = trim($request->query('key'));

                $allSubAccount = SubAccount::where('name', 'LIKE', '%' . $key . '%')
                    ->where('status', 'true')
                    ->with(['account' => function ($query) {
                        $query->orderBy('id', 'desc');
                    }])
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $allSubAccountCount = SubAccount::where('name', 'LIKE', '%' . $key . '%')
                    ->where('status', 'true')
                    ->count();

                $converted = arrayKeysToCamelCase($allSubAccount->toArray());
                $finalResult = [
                    'getAllSubAccount' => $converted,
                    'totalSubAccount' => $allSubAccountCount,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting sub account. Please try again later.'], 500);
            }
        } elseif ($request->query('type') === 'sa') {
            try {
                $pagination = getPagination($request->query());

                $allSubAccount = SubAccount::when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })->when($request->query('accountId'), function ($query) use ($request) {
                    return $query->whereIn('accountId', explode(',', $request->query('accountId')));
                })
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->with('account')
                    ->orderBy('id', 'desc')
                    ->get();

                $allSubAccountCount = SubAccount::when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })->when($request->query('accountId'), function ($query) use ($request) {
                    return $query->whereIn('accountId', explode(',', $request->query('accountId')));
                })
                    ->count();

                $converted = arrayKeysToCamelCase($allSubAccount->toArray());
                $finalResult = [
                    'getAllSubAccount' => $converted,
                    'totalSubAccount' => $allSubAccountCount,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting sub account. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'ma') {
            try {
                $allAccount = Account::orderBy('id', 'desc')->get();
                $converted = arrayKeysToCamelCase($allAccount->toArray());
                return response()->json($converted, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting main account. Please try again later.'], 500);
            }
        } else {
            try {
                $allAccount = Account::with(['subAccount.credit' => function ($query) {
                    $query->orderBy('id', 'desc');

                }, 'subAccount.debit' => function ($query) {
                    $query->orderBy('id', 'desc');

                }])->orderBy('id', 'desc')->get();
                $converted = arrayKeysToCamelCase($allAccount->toArray());
                return response()->json($converted, 200);
            } catch (Exception $error) {
                return response()->json(['error' => 'An error occurred during getting all. Please try again later.'], 500);
            }
        }
    }

    public function getSingleAccount(Request $request, $id): JsonResponse
    {
        try {
            $singleAccount = SubAccount::with(['debit' => function ($query) {
                $query->orderBy('id', 'desc');
            }, 'credit' => function ($query) {
                $query->orderBy('id', 'desc');
            }])->find($id);
            // calculate balance from total debit and credit

            $totalDebit = $singleAccount->debit->sum('amount');
            $totalCredit = $singleAccount->credit->sum('amount');
            $balance = $totalDebit - $totalCredit;
            $singleAccount->balance = $balance;

            $converted = arrayKeysToCamelCase($singleAccount->toArray());
            return response()->json($converted, 200);
        } catch (Exception $error) {
            return response()->json(['error' => 'An error occurred during getting single account. Please try again later.'], 500);
        }
    }

    //update the subAccount
    public function updateSubAccount(Request $request, $id): JsonResponse
    {
        try {

            if ($id <= 15) {
                return response()->json(['error' => 'You can not update default sub account'], 400);
            }

            $debit = Transaction::where('debitId', $id)->first();
            $credit = Transaction::where('creditId', $id)->first();

            if ($debit || $credit) {
                return response()->json(['error' => 'Transaction has already been maid in this account'], 400);
            }

            $account = SubAccount::findOrFail($id);

            if (!$account) {
                return response()->json(['error' => 'Sub Account not found'], 404);
            }

            $account->update($request->all());

            return response()->json(['message' => 'Update Successful'], 200);
        } catch (Exception $error) {
            return response()->json(['error' => 'An error occurred during login. Please try again later.'], 500);
        }
    }

    public function deleteSubAccount(Request $request, $id): JsonResponse
    {
        try {
            SubAccount::where('id', $id)->update([
                'status' => $request->input('status')
            ]);
            return response()->json('Sub Account deleted successfully', 200);
        } catch (Exception $error) {
            return response()->json(['error' => 'An error occurred during delete sub account. Please try again later.'], 500);
        }
    }
}
