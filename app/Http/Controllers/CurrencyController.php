<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{

    public function createSingleCurrency(Request $request): JsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {

                $data = json_decode($request->getContent(), true);
                $deletedCurrency = Currency::destroy($data);

                $deletedCounted = [
                    'count' => $deletedCurrency,
                ];
                return response()->json($deletedCounted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during deleting many Currency. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $currencyData = $request->json()->all();
                $createdCurrency = collect($currencyData)->map(function ($currency) {
                    return Currency::create([
                        'currencyName' => $currency['currencyName'],
                        'currencySymbol' => $currency['currencySymbol'],

                    ]);
                });

                $converted = arrayKeysToCamelCase($createdCurrency->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating many Currency. Please try again later.'], 500);
            }
        } else {
            try {
                $createdCurrency = Currency::create([
                    'currencyName' => $request->input('currencyName'),
                    'currencySymbol' => $request->input('currencySymbol')
                ]);

                $converted = arrayKeysToCamelCase($createdCurrency->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating Currency. Please try again later.'], 500);
            }
        }
    }

    public function getAllCurrency(Request $request)
    {
        if ($request->query('query') === 'all') {
            try {
                $currency = Currency::where('status', 'true')->orderBy('id', 'desc')->get();
                $converted = arrayKeysToCamelCase($currency->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        }else if ($request->query('query') === "search"){
            try {
                $pagination = getPagination($request->query());
                $currency = Currency::where('currencyName', 'like', '%' . $request->query('key') . '%')
                    ->where('status', $request->query('status'))
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $totalCount = Currency::where('currencyName', 'like', '%' . $request->query('key') . '%')
                    ->where('status', $request->query('status'))
                    ->count();

                $converted = arrayKeysToCamelCase($currency->toArray());
                return [
                    'getAllCurrency' => $converted,
                    'totalCurrency' => $totalCount
                ];
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }

        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $currency = Currency::where('status', $request->input('status'))
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $total = Currency::where('status', $request->input('status'))->count();
                $converted = arrayKeysToCamelCase($currency->toArray());
                return [
                    'getAllCurrency' => $converted,
                    'totalCurrency' => $total
                ];
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during fetching Currency. Please try again later.'], 500);
            }
        }
    }

    public function getSingleCurrency(Request $request, $id): JsonResponse
    {
        try {
            $currency = Currency::findorFail($id);
            $converted = arrayKeysToCamelCase($currency->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during fetching Currency. Please try again later.'], 500);
        }
    }


    public function updateSingleCurrency(Request $request, $id): JsonResponse
    {
        try {
            $currency = Currency::findorFail($id);
            $currency->update([
                'currencyName' => $request->input('currencyName') ?? $currency->currencyName,
                'currencySymbol' => $request->input('currencySymbol') ?? $currency->currencySymbol,
                'status' => $request->input('status') ?? $currency->status,
            ]);
            $converted = arrayKeysToCamelCase($currency->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during updating Currency. Please try again later.'], 500);
        }
    }

    public function deleteSingleCurrency(Request $request, $id): JsonResponse
    {
        try {
            $currency = Currency::where('id', $id)->update([
                'status' => $request->input('status'),
            ]);
            if (!$currency) {
                return response()->json(['error' => 'An error occurred during deleting Currency. Please try again later.'], 404);
            }
            return response()->json(['message' => 'Currency deleted successfully.'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during deleting Currency. Please try again later.'], 500);
        }
    }
}
