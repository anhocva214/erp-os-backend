<?php

namespace App\Http\Controllers;

use App\Models\ProductSubCategory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductSubCategoryController extends Controller
{
    //create productSubCategory controller method
    public function createSingleProductSubCategory(Request $request): JsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedProductSubCategory = ProductSubCategory::destroy($ids);

                $deletedCount = [
                    'count' => $deletedProductSubCategory
                ];

                return response()->json($deletedCount, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during delete ProductSubCategory. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'createmany') {
            try {
                $subCategoryData = json_decode($request->getContent(), true);

                $createdProductSubCategory = collect($subCategoryData)->map(function ($item) {
                    return ProductSubCategory::firstOrCreate([
                        'name' => $item['name'],
                        'productCategoryId' => $item['productCategoryId'],
                    ]);
                });

                $result = [
                    'count' => count($createdProductSubCategory),
                ];

                return response()->json($result, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create ProductSubCategory. Please try again later.'], 500);
            }
        } else {
            try {
                $categoryData = json_decode($request->getContent(), true);

                $createdProductSubCategory = ProductSubCategory::create([
                    'name' => $categoryData['name'],
                    'productCategoryId' => $categoryData['productCategoryId'],
                ]);

                $converted = arrayKeysToCamelCase($createdProductSubCategory->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create ProductSubCategory. Please try again later.'], 500);
            }
        }
    }

    // get all productSubCategory controller method
    public function getAllProductSubCategory(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $getAllProductSubCategory = ProductSubCategory::orderBy('id', 'desc')
                ->with(['product'=> function($query){
                    $query->orderBy('id', 'desc');
                }])
                    ->get();

                $currentAppUrl = url('/');
                collect($getAllProductSubCategory)->map(function ($subCategory) use ($currentAppUrl) {
                    $subCategory->product->map(function ($item) use ($currentAppUrl) {
                        if ($item->productThumbnailImage) {
                            $item->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$item->productThumbnailImage}";
                        }

                        return $item;
                    });
                    return $subCategory;
                });

                $converted = arrayKeysToCamelCase($getAllProductSubCategory->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductSubCategory. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'info') {
            try {
                $aggregation = ProductSubCategory::where('status', 'true')
                    ->count();

                $result = [
                    '_count' => [
                        'id' => $aggregation,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductSubCategory. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());
                $getAllProductSubCategory = ProductSubCategory::where('name', 'LIKE', '%' . $request->query('key') . '%')
                ->orderBy('id', 'desc')
                ->skip($pagination['skip'])
                ->take($pagination['limit'])
                ->get();

                $totalProductCategory = ProductSubCategory::where('name', 'LIKE', '%' . $request->query('key') . '%')
                ->count();
    
                $currentAppUrl = url('/');
                collect($getAllProductSubCategory)->map(function ($subCategory) use ($currentAppUrl) {
                    $subCategory->product->map(function ($item) use ($currentAppUrl) {
                        if ($item->productThumbnailImage) {
                            $item->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$item->productThumbnailImage}";
                        }

                        return $item;
                    });
                    return $subCategory;
                });

                $converted = arrayKeysToCamelCase($getAllProductSubCategory->toArray());
                $finalResult = [
                    'getAllProductSubCategory' => $converted,
                    'totalProductSubCategory' => $totalProductCategory,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductSubCategory. Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $getAllProductSubCategory = ProductSubCategory::
                when($request->query('status'), function ($query) use ($request) {
                   return $query->whereIn('status', explode(',', $request->query('status')));
                }) 
                ->when($request->query('productCategoryId'), function ($query) use ($request) {
                    return $query->whereIn('productCategoryId', explode(',', $request->query('productCategoryId')));
                })
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $totalProductSubCategory = ProductSubCategory::
                when($request->query('status'), function ($query) use ($request) {
                   return $query->whereIn('status', explode(',', $request->query('status')));
                }) 
                ->when($request->query('productCategoryId'), function ($query) use ($request) {
                    return $query->whereIn('productCategoryId', explode(',', $request->query('productCategoryId')));
                })
                    ->count();

                $currentAppUrl = url('/');
                collect($getAllProductSubCategory)->map(function ($subCategory) use ($currentAppUrl) {
                    $subCategory->product->map(function ($item) use ($currentAppUrl) {
                        if ($item->productThumbnailImage) {
                            $item->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$item->productThumbnailImage}";
                        }

                        return $item;
                    });
                    return $subCategory;
                });

                $converted = arrayKeysToCamelCase($getAllProductSubCategory->toArray());
                $finalResult = [
                    'getAllProductSubCategory' => $converted,
                    'totalProductSubCategory' => $totalProductSubCategory,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductSubCategory. Please try again later.'], 500);
            }
        } else{
            return response()->json(['error'=> 'Invalid Query!'], 500);
        }
    }

    public function getPublicProductSubCategory(Request $request): JsonResponse
    {
        try {
            $getAllProductSubCategory = ProductSubCategory::orderBy('id', 'desc')
                ->where('status', 'true')
                ->get();

            $converted = arrayKeysToCamelCase($getAllProductSubCategory->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting ProductSubCategory. Please try again later.'], 500);
        }
    }
    // get a single productSubCategory controller method
    public function getSingleProductSubCategory(Request $request, $id): JsonResponse
    {
        try {
            $singleProductSubCategory = ProductSubCategory::where('id', (int) $id)
                ->with('product')
                ->first();

            $currentAppUrl = url('/');
            $singleProductSubCategory->product->map(function ($item) use ($currentAppUrl) {
                if ($item->productThumbnailImage) {
                    $item->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$item->productThumbnailImage}";
                }
                return $item;
            });

            $converted = arrayKeysToCamelCase($singleProductSubCategory->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting ProductSubCategory. Please try again later.'], 500);
        }
    }

    // update a single productCategory controller method
    public function updateSingleProductSubCategory(Request $request, $id): JsonResponse
    {
        try {
            $updatedProductSubCategory = ProductSubCategory::where('id', (int) $id)
                ->update($request->all());

            if (!$updatedProductSubCategory) {
                return response()->json(['error' => 'Failed To Update ProductSubCategory'], 404);
            }
            return response()->json(['message' => 'Product Sub Category updated Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during update ProductSubCategory. Please try again later.'], 500);
        }
    }

    // delete a single productCategory controller method
    public function deleteSingleProductSubCategory(Request $request, $id): JsonResponse
    {
        try {
            $deletedProductSubCategory = ProductSubCategory::where('id', (int) $id)
                ->update([
                    'status' => $request->input('status'),
                ]);

            if (!$deletedProductSubCategory) {
                return response()->json(['error' => 'Failed To Update ProductSubCategory'], 404);
            }
            return response()->json(['message' => 'Product Sub Category deleted Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete ProductSubCategory. Please try again later.'], 500);
        }
    }
}
