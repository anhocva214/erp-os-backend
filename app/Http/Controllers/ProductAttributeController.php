<?php

namespace App\Http\Controllers;

use App\Models\ProductAttribute;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductAttributeController extends Controller
{
    //create product attribute
    public function createProductAttribute(Request $request): JsonResponse
    {
        DB::beginTransaction();
        if ($request->query('query') === 'deletemany') {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedManyProductAttribute = ProductAttribute::destroy($ids);

                $deletedCount = [
                    'count' => $deletedManyProductAttribute
                ];
                DB::commit();
                return response()->json($deletedCount, 200);
            } catch (Exception $exception) {
                DB::rollBack();
                return response()->json(['error' => $exception->getMessage()], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $attributeData = json_decode($request->getContent(), true);
                collect($attributeData)->map(function ($item) {
                    return $item['name'];
                });

                $existingAttributes = ProductAttribute::whereIn('name', $attributeData)->get();
                if ($existingAttributes->isNotEmpty()) {
                    $existingNames = $existingAttributes->pluck('name')->toArray();
                    $arrayUnique = array_unique($existingNames);
                    $newArray = implode(', ', $arrayUnique);

                    return response()->json(['error' => 'Product Attribute ' . $newArray . ' already exists'], 409);
                }

                $createdProductAttribute = collect($attributeData)->map(function ($item) {
                    return ProductAttribute::create([
                        'name' => $item['name'],
                    ]);
                });
                DB::commit();
                return response()->json(['count' => count($createdProductAttribute)], 201);

            } catch (Exception $exception) {
                DB::rollBack();
                return response()->json(['error' => $exception->getMessage()], 500);
            }
        } else {
            try {
                $attributeData = json_decode($request->getContent(), true);
                $createdProductAttribute = ProductAttribute::create([
                    'name' => $attributeData['name'],
                ]);
                $converted = arrayKeysToCamelCase($createdProductAttribute->toArray());
                DB::commit();
                return response()->json($converted, 201);
            } catch (Exception $exception) {
                DB::rollBack();
                return response()->json(['error' => $exception->getMessage()], 500);
            }
        }
    }

    //get all product attribute
    public function getAllProductAttribute(Request $request): JsonResponse
    {
        if (request()->query('query') === 'all') {
            try {
                $productAttribute = ProductAttribute::with('productAttributeValue')
                    ->where('status', 'true')
                    ->orderBy('id', 'desc')
                    ->get();

                $converted = arrayKeysToCamelCase($productAttribute->toArray());
                return response()->json($converted, 200);
            } catch (Exception $exception) {
                return response()->json(['error' => $exception->getMessage()], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination(request()->query());

                $productAttribute = ProductAttribute::with(['productAttributeValue.productProductAttributeValue'])->orderBy('id', 'desc')
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $total = ProductAttribute::where('status', $request->query('status'))->count();

                $converted = arrayKeysToCamelCase($productAttribute->toArray());
                $result = [
                    'getAllProductAttribute' => $converted,
                    'totalProductAttribute' => $total
                ];
                return response()->json($result, 200);
            } catch (Exception $exception) {
                return response()->json(['error' => $exception->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid Query'], 404);
        }
    }

    //single product attribute
    public function getSingleProductAttribute($id): JsonResponse
    {
        try {
            $productAttribute = ProductAttribute::with(['productAttributeValue.productProductAttributeValue'])->find($id);

            if (!$productAttribute) {
                return response()->json(['error' => 'Product Attribute Not Found'], 404);
            }

            $converted = arrayKeysToCamelCase($productAttribute->toArray());
            return response()->json($converted, 200);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    //update product attribute
    public function updateProductAttribute(Request $request, $id): JsonResponse
    {
        try {
            $productAttribute = ProductAttribute::find($id);

            if (!$productAttribute) {
                return response()->json(['error' => 'Product Attribute Not Found'], 404);
            }

            $productAttribute->update($request->all());
            return response()->json($productAttribute, 200);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    //delete product attribute
    public function deleteProductAttribute(Request $request, $id): JsonResponse
    {
        try {
            $productAttribute = ProductAttribute::where('id', $id)->update([
                'status' => $request->input('status')
            ]);
            if (!$productAttribute) {
                return response()->json(['error' => 'Product Attribute Not Found'], 404);
            }
            return response()->json(['message' => 'Product Attribute Deleted Successfully'], 200);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }
}
