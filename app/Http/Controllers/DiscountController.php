<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Discount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function createSingleDiscount(Request $request): JsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {

                $data = json_decode($request->getContent(), true);
                $deletedDiscount = Discount::destroy($data);

                $deletedCounted = [
                    'count' => $deletedDiscount,
                ];
                return response()->json($deletedCounted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during deleting many Discount. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $discountData = $request->json()->all();
                $createdDiscount = collect($discountData)->map(function ($discount) {
                    return Discount::create([
                        'value' => $discount['value'],
                        'type' => $discount['type'],
                        'startDate' => $discount['startDate'],
                        'endDate' => $discount['endDate']
                    ]);
                });

                $converted = arrayKeysToCamelCase($createdDiscount->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating many Discount. Please try again later.'], 500);
            }
        } else {
            try {
                $createdDiscount = Discount::create([
                    'value' => $request->input('value'),
                    'type' => $request->input('type'),
                    'startDate' => $request->input('startDate'),
                    'endDate' => $request->input('endDate')
                ]);

                $converted = arrayKeysToCamelCase($createdDiscount->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating Discount. Please try again later.'], 500);
            }
        }
    }

    public function getAllDiscount(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $discount = Discount::where('status', 'true')->orderBy('id', 'desc')->get();
                $converted = arrayKeysToCamelCase($discount->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting all Discount. Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $discount = Discount::where('status', $request->input('status'))
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();
                $totaldiscount = Discount::where('status', $request->input('status'))->count();
                $converted = arrayKeysToCamelCase($discount->toArray());

                $finalResult = [
                    'getAllDiscount' => $converted,
                    'totalDiscount' => $totaldiscount
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting all Discount. Please try again later.'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid query'], 500);
        }
    }

    public function getSingleDiscount(Request $request, $id): JsonResponse
    {
        try {
            $discount = Discount::findOrFail($id);

            if (!$discount) {
                return response()->json(['error' => 'Discount not found.'], 404);
            }

            $converted = arrayKeysToCamelCase($discount->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting single Discount. Please try again later.'], 500);
        }
    }

    public function updateSingleDiscount(Request $request, $id): JsonResponse
    {
        try {
            $discount = Discount::findOrFail($id);

            if (!$discount) {
                return response()->json(['error' => 'Discount not found.'], 404);
            }

            $discount->update([
                'value' => $request->input('value') ?? $discount->value,
                'type' => $request->input('type') ?? $discount->type,
                'startDate' => $request->input('startDate') ?? $discount->startDate,
                'endDate' => $request->input('endDate') ?? $discount->endDate,
                'status' => $request->input('status') ?? $discount->status,
            ]);

            $converted = arrayKeysToCamelCase($discount->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during updating single Discount. Please try again later.'], 500);
        }
    }

    public function deleteSingleDiscount(Request $request, $id): JsonResponse
    {
        try {
            $discount = Discount::where('id', $id)->update([
                'status' => $request->input('status'),
            ]);
            if (!$discount) {
                return response()->json(['error' => 'An error occurred during deleting a Discount. Please try again later.'], 500);
            }
            return response()->json(['message' => 'Discount has been hided successfully.'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during deleting single Discount. Please try again later.'], 500);
        }
    }
}
