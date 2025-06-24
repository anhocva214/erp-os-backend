<?php

namespace App\Http\Controllers;

use App\Models\ProductVat;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVatController extends Controller
{
    //create product vat
    public function createProductVat(Request $request): JsonResponse
    {
        if ($request->query('query') === "deletemany") {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedProductVat = ProductVat::destroy($ids);

                $deletedCount = [
                    'count' => $deletedProductVat
                ];

                return response()->json($deletedCount, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during delete ProductVat. Please try again later.'], 500);
            }
        } else if ($request->query('query') === "createmany") {
            try {
                $vatData = json_decode($request->getContent(), true);

                $createdProductVat = collect($vatData)->map(function ($item) {
                    return ProductVat::firstOrCreate([
                        'title' => $item['title'],
                        'percentage' => $item['percentage'],
                    ]);
                });

                $result = [
                    'count' => count($createdProductVat),
                ];

                return response()->json($result, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create ProductVat. Please try again later.'], 500);
            }
        } else {
            try {
                $vatData = json_decode($request->getContent(), true);

                $createdProductVat = ProductVat::create([
                    'title' => $vatData['title'],
                    'percentage' => $vatData['percentage'],
                ]);

                $converted = arrayKeysToCamelCase($createdProductVat->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create ProductVat. Please try again later.'], 500);
            }
        }
    }

    //productVatDetails
    public function productVatDetails(): JsonResponse
    {
        try {
            // Calculate total VAT received
            $totalVatReceived = Transaction::where('debitId', 1)
                ->where('creditId', 15)
                ->sum('amount');

            // Calculate total VAT given
            $totalVatGiven = Transaction::where('debitId', 16)
                ->where('creditId', 1)
                ->sum('amount');

            // Calculate total VAT balance
            $totalVatBalance = $totalVatReceived - $totalVatGiven;

            return response()->json([
                'totalVatGiven' => $totalVatGiven,
                'totalVatReceived' => $totalVatReceived,
                'totalVat' => $totalVatBalance,
            ], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting ProductVat. Please try again later.'], 500);
        }
    }

    //get all product vat
    public function getAllProductVat(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $productVat = ProductVat::all();
                $converted = arrayKeysToCamelCase($productVat->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductVat. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'info') {
            try {
                //aggregate function
                $aggregations = ProductVat::selectRaw('COUNT(id) as counted')->where('status', 'true')->first();

                $result = [
                    '_count' => [
                        'id' => $aggregations->counted,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductVat. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());
                $key = trim($request->query('key'));

                $getAllProductVat = ProductVat::Where('title', 'LIKE', '%' . $key . '%')
                    ->orWhere('percentage', 'LIKE', '%' . $key . '%')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();


                $getAllProductVatCount = ProductVat::Where('title', 'LIKE', '%' . $key . '%')
                    ->orWhere('percentage', 'LIKE', '%' . $key . '%')
                    ->count();

                $converted = arrayKeysToCamelCase($getAllProductVat->toArray());
                $result = [
                    'getAllProductVat' => $converted,
                    'totalProductVat' => $getAllProductVatCount,
                ];
                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductVat. Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());

                $getAllProductVat = ProductVat::when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();


                $getAllProductVatCount = ProductVat::when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })
                    ->count();

                $converted = arrayKeysToCamelCase($getAllProductVat->toArray());
                $result = [
                    'getAllProductVat' => $converted,
                    'totalProductVat' => $getAllProductVatCount,
                ];
                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductVat. Please try again later.'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid query parameter'], 400);
        }
    }

    //update product vat
    public function updateProductVat(Request $request, $id): JsonResponse
    {
        try {
            $updatedProductVat = ProductVat::where('id', (int) $id)->update($request->all());

            if (!$updatedProductVat) {
                return response()->json(['error' => 'Product vat not found'], 404);
            }
            return response()->json(['message' => 'product vat update successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during update ProductVat. Please try again later.'], 500);
        }
    }

    //delete product vat
    public function deleteProductVat(Request $request, $id): JsonResponse
    {
        try {
            $deletedProductVat = ProductVat::where('id', (int) $id)->update(['status' => $request->input('status')]);

            if (!$deletedProductVat) {
                return response()->json(['error' => 'Failed To Delete ProductVat'], 404);
            }
            return response()->json(['message' => 'product vat delete successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete ProductVat. Please try again later.'], 500);
        }
    }
}
