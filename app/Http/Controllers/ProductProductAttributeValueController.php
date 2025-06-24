<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ProductProductAttributeValue;

class ProductProductAttributeValueController extends Controller
{


    public function createProductProductAttributeValue(Request $request): JsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedManyProductProductAttributeValue = ProductProductAttributeValue::destroy($ids);

                $deletedCount = [
                    'count' => $deletedManyProductProductAttributeValue
                ];
                return response()->json($deletedCount, 200);
            } catch (Exception $exception) {
                return response()->json(['error' => 'An error occurred during deleting many ProductProductAttributeValue. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $attributeData = json_decode($request->getContent(), true);
                $createdProductProductAttributeValue = collect($attributeData)->map(function ($item) {
                    return ProductProductAttributeValue::firstOrCreate([
                        'productId' => $item['productId'],
                        'productAttributeValueId' => $item['productAttributeValueId'],
                    ]);
                });

                $result = [
                    'count' => count($createdProductProductAttributeValue),
                ];

                return response()->json($result, 201);
            } catch (Exception $exception) {
                return response()->json(['error' => 'An error occurred during creating many ProductProductAttributeValue. Please try again later.'], 500);
            }
        } else {
            try {
                $createdProductProductAttributeValue = ProductProductAttributeValue::create([
                    'productId' => $request->input('productId'),
                    'productAttributeValueId' => $request->input('productAttributeValueId'),
                ]);

                $converted = arrayKeysToCamelCase($createdProductProductAttributeValue->toArray());
                return response()->json($converted, 201);
            } catch (Exception $exception) {
                return response()->json(['error' => 'An error occurred during creating ProductProductAttributeValue. Please try again later.'], 500);
            }
        }
    }


    public function getAllProductProductAttributeValue(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $productProductAttributeValue = ProductProductAttributeValue::where('status', 'true')->orderBy('id', 'desc')->get();
                $converted = arrayKeysToCamelCase($productProductAttributeValue->toArray());
                return response()->json($converted, 200);
            } catch (Exception $exception) {
                return response()->json(['error' => 'An error occurred during getting all ProductProductAttributeValue. Please try again later.'], 500);
            }
        } else {
            try {
                $pagination = getPagination($request->query());

                $productProductAttributeValue = ProductProductAttributeValue::with(['product', 'productAttributeValue.productAttribute'])->where('status', $request->query('status'))
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();
                $total = ProductProductAttributeValue::where('status', $request->query('status'))->count();

                $converted = arrayKeysToCamelCase($productProductAttributeValue->toArray());

                $result = [
                    'getAllProductProductAttributeValue' => $converted,
                    'totalProductProductAttributeValue' => $total
                ];

                return response()->json($result, 200);
            } catch (Exception $exception) {
                return response()->json(['error' => 'An error occurred during getting ProductProductAttributeValue. Please try again later.'], 500);
            }
        }
    }


    public function getSingleProductProductAttributeValue($id): JsonResponse
    {
        try {
            $productProductAttributeValue = ProductProductAttributeValue::with(['product', 'productAttributeValue.productAttribute'])->find($id);

            if (!$productProductAttributeValue) {
                return response()->json(['error' => 'ProductProductAttributeValue not found.'], 404);
            }

            $converted = arrayKeysToCamelCase($productProductAttributeValue->toArray());
            return response()->json($converted, 200);
        } catch (Exception $exception) {
            return response()->json(['error' => 'An error occurred during getting Single ProductProductAttributeValue. Please try again later.'], 404);
        }
    }

    public function updateProductProductAttributeValue(Request $request, $id): JsonResponse
    {
        try {
            $productProductAttributeValue = ProductProductAttributeValue::findOrFail($id);

            if (!$productProductAttributeValue) {
                return response()->json(['error' => 'ProductProductAttributeValue not found.'], 404);
            }

            $productProductAttributeValue->update([
                'productId' => $request->input('productId') ?? $productProductAttributeValue->productId,
                'productAttributeValueId' => $request->input('productAttributeValueId') ?? $productProductAttributeValue->productAttributeValueId,
            ]);

            $converted = arrayKeysToCamelCase($productProductAttributeValue->toArray());

            return response()->json($converted, 200);
        } catch (Exception $exception) {
            return response()->json(['error' => 'An error occurred during updating ProductProductAttributeValue. Please try again later.'], 404);
        }
    }

    public function deleteProductProductAttributeValue(Request $request, $id): JsonResponse
    {
        try {
            $productProductAttributeValue = ProductProductAttributeValue::where('id', $id)->update([
                'status' => $request->input('status')
            ]);
            if (!$productProductAttributeValue) {
                return response()->json(['error' => 'An error occurred during deleting ProductProductAttributeValue. Please try again later.'], 404);
            }
            return response()->json(['success' => 'ProductProductAttributeValue deleted successfully.'], 200);
        } catch (Exception $exception) {
            return response()->json(['error' => 'An error occurred during deleting ProductProductAttributeValue. Please try again later.'], 404);
        }
    }
}
