<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ProductBrand;
use Firebase\JWT\{JWT, Key};
use function PHPSTORM_META\map;
use App\Models\ProductSubCategory;
use Illuminate\Support\Collection;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\{JsonResponse, Request};
use App\Models\{Images, Product, ProductColor, ProductProductAttributeValue, ProductVat};

class ProductController extends Controller
{
    //create a single product controller method
    public function createSingleProduct(Request $request): JsonResponse
    {
        DB::beginTransaction();

        if ($request->query('query') === 'deletemany') {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedProduct = Product::whereIn('id', $ids)->delete();

                $deletedCount = [
                    'count' => $deletedProduct
                ];
                DB::commit();
                return response()->json($deletedCount, 200);
            } catch (Exception) {
                DB::rollBack();
                return response()->json(['error' => 'An error occurred during delete product.Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'createmany') {
            try {

                $productData = json_decode($request->getContent(), true);

                collect($productData)->map(function ($item) {
                    $product = Product::where('sku', $item['sku'])->first();
                    if ($product) {
                        return null;
                    }
                    return $item;
                })->filter(function ($item) {
                    return $item !== null;
                })->toArray();

                $productsWithMissingBrandIds = collect($productData)->filter(function ($item) {
                    return !ProductBrand::where('id', $item['productBrandId'])->exists();
                })->toArray();


                $productsWithMissingSubCategoryIds = collect($productData)->filter(function ($item) {
                    return !ProductSubCategory::where('id', $item['productSubCategoryId'])->exists();
                })->toArray();

                if (!empty($productsWithMissingBrandIds) || !empty($productsWithMissingSubCategoryIds)) {

                    //map all product name with missing brandId
                    $productsWithMissingBrandIds = collect($productsWithMissingBrandIds)->map(function ($item) {
                        return $item['name'];
                    })->toArray();
                    $productNameWithMissingBrandId = implode(',', $productsWithMissingBrandIds);
                    //map all product name with missing subCategoryId
                    $productsWithMissingSubCategoryIds = collect($productsWithMissingSubCategoryIds)->map(function ($item) {
                        return $item['name'];
                    })->toArray();
                    $productNameWithMissingSubCategoryId = implode(',', $productsWithMissingSubCategoryIds);

                    if (!empty($productsWithMissingBrandIds) && !empty($productsWithMissingSubCategoryIds)) {
                        return response()->json(['error' => "{$productNameWithMissingBrandId} Product with missing BrandId And {$productNameWithMissingSubCategoryId} Product with missing subCategoryId"], 500);
                    }
                    if (!empty($productsWithMissingBrandIds)) {
                        return response()->json(['error' => "{$productNameWithMissingBrandId} Product with missing BrandId"], 500);
                    }
                    if (!empty($productsWithMissingSubCategoryIds)) {
                        return response()->json(['error' => "{$productNameWithMissingSubCategoryId} Product with missing subCategoryId"], 500);
                    }
                }

                //check if product already exists
                $productSku = Product::whereIn('sku', array_column($productData, 'sku'))->get();
                if (count($productSku) > 0) {
                    $sku = $productSku->map(function ($item) {
                        return $item->sku;
                    });
                    $sku = implode(',', $sku->toArray());
                    return response()->json(['error' => $sku . ' Product sku already exists.'], 409);
                }

                if (count($productData) === 0) {
                    return response()->json(['error' => 'All products already exists.'], 409);
                }

                $createdProduct = collect($productData)->map(function ($item) {
                    return Product::firstOrCreate($item);
                });

                $result = [
                    'count' => count($createdProduct),
                ];
                DB::commit();
                return response()->json($result, 201);
            } catch (Exception $err) {
                DB::rollBack();
                return response()->json(['error' => 'An error occurred during create product.Please try again later.'], 500);
            }
        } else {
            try {
                $request->validate([
                    'name' => 'required|string',
                    'sku' => 'required|string',
                ]);

                $product = Product::where('sku', $request->input('sku'))->first();
                if ($product) {
                    return response()->json(['error' => 'Product sku already exists.'], 500);
                }

                $file_paths = $request->file_paths;

                $createdProduct = Product::create([
                    'name' => $request->input('name'),
                    'productThumbnailImage' => $file_paths[0] ?? null,
                    'productSubCategoryId' => (int) $request->input('productSubCategoryId') ? (int) $request->input('productSubCategoryId') : null,
                    'productBrandId' => (int) $request->input('productBrandId') ? (int) $request->input('productBrandId') : null,
                    'description' => $request->input('description') ?? null,
                    'sku' => $request->input('sku'),
                    'productQuantity' => (int) $request->input('productQuantity') ?? null,
                    'productPurchasePrice' => takeUptoThreeDecimal((float) $request->input('productPurchasePrice')) ?? null,
                    'productSalePrice' => takeUptoThreeDecimal((float) $request->input('productSalePrice')) ?? null,
                    'uomId' => $request->input('uomId') ?? null,
                    'uomValue' => takeUptoThreeDecimal((float) $request->input('uomValue')) ?? null,
                    'reorderQuantity' => (int) $request->input('reorderQuantity') ?? null,
                    'productVatId' => (int) $request->input('productVatId') ? (int) $request->input('productVatId') : null,
                    'discountId' => (int) $request->input('discountId') ? (int) $request->input('discountId') : null,
                ]);


                if ($createdProduct && $request->input('productSubCategoryId')) {
                    $createdProduct->load('productSubCategory');
                }

                if ($createdProduct && $request->input('productBrandId')) {
                    $createdProduct->load('productBrand');
                }

                // add color code against createdProduct Id
                $colors = $request->input('colors');
                $colorsArray = explode(',', $colors);

                if ($createdProduct && $colors) {
                    foreach ($colorsArray as $item) {
                        ProductColor::create([
                            'productId' => $createdProduct->id,
                            'colorId' => $item,
                        ]);
                    }
                }

                if ($createdProduct && $file_paths) {
                    // Take only the elements from index 1 to 8 from the array
                    $galleryImages = array_slice($file_paths, 1, 8);
                    // Add new gallery images
                    foreach ($galleryImages as $item) {
                        Images::create([
                            'productId' => $createdProduct->id,
                            'imageName' => $item,
                        ]);
                    }
                }

                //product attribute value array given
                $productAttributeValue = $request->input('productAttributeValueId');
                $productAttributeValueArray = explode(',', $productAttributeValue);

                if ($createdProduct && $productAttributeValue) {
                    foreach ($productAttributeValueArray as $item) {
                        ProductProductAttributeValue::create([
                            'productId' => $createdProduct->id,
                            'productAttributeValueId' => $item,
                        ]);
                    }
                }

                $currentAppUrl = url('/');
                if ($createdProduct) {
                    $createdProduct->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$createdProduct->productThumbnailImage}";
                }

                $converted = arrayKeysToCamelCase($createdProduct->toArray());
                DB::commit();
                return response()->json($converted, 201);
            } catch (Exception $err) {
                DB::rollBack();
                return response()->json(['error' => 'An error occurred during create product.Please try again later.'], 500);
            }
        }
    }

    public function getAllProduct(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                // Use cached optimized query
                $cacheKey = 'products_all_optimized';
                $getAllProduct = Cache::remember($cacheKey, 900, function () { // 15 minutes cache
                    return Product::orderBy('id', 'desc')
                        ->select('id', 'name', 'productQuantity', 'productSalePrice', 'productPurchasePrice', 'status', 'productThumbnailImage')
                        ->with([
                            'productVat:id,percentage',
                            'productSubCategory:id,name',
                            'productBrand:id,name',
                            'uom:id,name'
                        ])
                        ->where('status', 'true')
                        ->get();
                });

                $currentAppUrl = url('/');
                $getAllProduct->each(function ($product) use ($currentAppUrl) {
                    if ($product->productThumbnailImage) {
                        $product->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$product->productThumbnailImage}";
                    }
                });

                $converted = arrayKeysToCamelCase($getAllProduct->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'sku') {
            try {
                $product = Product::where('sku', $request->query('key'))->first();
                $sku = "false";
                if ($product) {
                    $sku = "true";
                }
                return response()->json(["status" => $sku], 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'name') {
            try {
                $product = Product::where('name', $request->query('key'))->first();
                $name = "false";
                if ($product) {
                    $name = "true";
                }
                return response()->json(["status" => $name], 200);
            } catch (Exception $err) {
                return response()->json(['error' => $err->getMessage()], 500);
            }
        } else if ($request->query('query') === 'info') {
            try {
                $aggregation = Product::where('status', 'true')
                    ->count();

                $result = [
                    '_count' => [
                        'id' => $aggregation,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'search') {
            try {

                $getAllProduct = Product::Where('name', 'LIKE', '%' . $request->query('key') . '%')
                    ->orWhere('sku', 'LIKE', '%' . $request->query('key') . '%')
                    ->with('productSubCategory', 'productColor.color', 'productVat:id,title,percentage', 'uom:id,name:id,name', 'productProductAttributeValue', 'productProductAttributeValue.productAttributeValue', 'discount:id,value,type')
                    ->orderBy('id', 'desc')
                    ->get();

                $totalProductcount = Product::Where('name', 'LIKE', '%' . $request->query('key') . '%')
                    ->orWhere('sku', 'LIKE', '%' . $request->query('key') . '%')
                    ->count();


                // remove productPurchasePrice
                $getAllProduct->map(function ($item) {
                    unset ($item->productPurchasePrice);
                });


               

                $currentAppUrl = url('/');
                collect($getAllProduct)->map(function ($product) use ($currentAppUrl) {
                    if ($product->productThumbnailImage) {
                        $product->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$product->productThumbnailImage}";
                    }
                    return $product;
                });

                $converted = arrayKeysToCamelCase($getAllProduct->toArray());


                $finalResult = [
                    'getAllProduct' => $converted,
                    'totalProduct' => $totalProductcount,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'card') {
            try {
                //get all product count
                $getAllProduct = Product::where('status', 'true')
                    ->count();

                //get all product quantity count
                $getAllProductQuantity = Product::where('status', 'true')->where('productQuantity', '>', 0)
                    ->count();

                //get all product sale price
                $getAllProductSalePrice = Product::where('status', 'true')
                    ->sum('productSalePrice');

                //get all product purchase price
                $getAllProductPurchasePrice = Product::where('status', 'true')
                    ->sum('productPurchasePrice');

                //get all product reorder quantity
                $getAllProductReorderQuantity = Product::where('status', 'true')->where('productQuantity', '<=', DB::raw('reorderQuantity'))
                    ->count();

                $result = [
                    'uniqueProduct' => (int) $getAllProductQuantity,
                    'totalProductCount' => $getAllProduct,
                    'inventorySalesValue' => $getAllProductSalePrice,
                    'inventoryPurchaseValue' => $getAllProductPurchasePrice,
                    'shortProductCount' => $getAllProductReorderQuantity,
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'report') {
            try {
                $products = Product::where('status', 'true')
                    ->with('productSubCategory', 'productBrand', 'productColor.color', 'productVat:id,title,percentage', 'uom:id,name:id,name', 'productProductAttributeValue', 'productProductAttributeValue.productAttributeValue')
                    ->orderBy('id', 'desc')
                    ->get();
                $products->each(function ($product) {
                    $product->totalSalePrice = $product->productQuantity * $product->productSalePrice;
                    $product->totalPurchasePrice = $product->productQuantity * $product->productPurchasePrice;
                });
                // calculate total product quantity and total salePrice total purchasePrice from products and attach it to aggregations

                $aggregations = [
                    '_count' => [
                        'id' => $products->count(),
                    ],
                    '_sum' => [
                        'totalProductQuantity' => $products->sum('productQuantity'),
                        'totalSalePrice' => $products->sum('totalSalePrice'),
                        'totalPurchasePrice' => $products->sum('totalPurchasePrice'),
                    ],
                ];

                $converted = arrayKeysToCamelCase($products->toArray());
                $finalResult = [
                    'aggregations' => $aggregations,
                    'getAllProduct' => $converted,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $getAllProduct = Product::when($request->query('productSubCategoryId'), function ($query) use ($request) {
                    return $query->whereIn('productSubCategoryId', explode(',', $request->query('productSubCategoryId')));
                })
                    ->when($request->query('productBrandId'), function ($query) use ($request) {
                        return $query->whereIn('productBrandId', explode(',', $request->query('productBrandId')));
                    })
                    ->when($request->query('uomId'), function ($query) use ($request) {
                        return $query->whereIn('uomId', explode(',', $request->query('uomId')));
                    })
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->orderBy('id', 'desc')
                    ->with('productVat', 'productSubCategory', 'productBrand', 'productColor.color', 'uom:id,name:id,name', 'productVat:id,title,percentage' , 'discount:id,value,type,startDate,endDate')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();


                $totalProductcount = Product::when($request->query('productSubCategoryId'), function ($query) use ($request) {
                    return $query->whereIn('productSubCategoryId', explode(',', $request->query('productSubCategoryId')));
                })
                    ->when($request->query('productBrandId'), function ($query) use ($request) {
                        return $query->whereIn('productBrandId', explode(',', $request->query('productBrandId')));
                    })
                    ->when($request->query('uomId'), function ($query) use ($request) {
                        return $query->whereIn('uomId', explode(',', $request->query('uomId')));
                    })
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->count();


                $currentAppUrl = url('/');
                collect($getAllProduct)->map(function ($product) use ($currentAppUrl) {
                    if ($product->productThumbnailImage) {
                        $product->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$product->productThumbnailImage}";
                    }
                    return $product;
                });

                $converted = arrayKeysToCamelCase($getAllProduct->toArray());
                $finalResult = [
                    'getAllProduct' => $converted,
                    'totalProduct' => $totalProductcount,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid Query!'], 400);
        }
    }

    public function getAllProductPublic(Request $request): JsonResponse
    {
        try {
            if ($request->query("query") === "all") {
                $pagination = getPagination($request->query());
                $getAllProduct = Product::orderBy('id', 'desc')
                    ->with('productVat', 'productSubCategory', 'productBrand', 'productColor.color', 'discount', 'galleryImage', 'productProductAttributeValue.productAttributeValue.productAttribute')
                    ->with('productSubCategory', 'productBrand', 'productColor.color', 'discount', 'galleryImage', 'productProductAttributeValue.productAttributeValue.productAttribute', 'uom:id,name')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

               
                $currentAppUrl = url('/');
                $getAllProduct->each(function ($product) use ($currentAppUrl) {
                    if ($product->productThumbnailImage) {
                        $product->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$product->productThumbnailImage}";
                    }
                });

                //unset productPurchasePrice, uomValue,purchaseInvoiceId,reorderQuantity
                $getAllProduct->map(function ($item) {
                    unset ($item->productPurchasePrice);
                    unset ($item->uomValue);
                    unset ($item->purchaseInvoiceId);
                    unset ($item->reorderQuantity);
                    return $item;
                });

                $converted = $this->getKeysToCamelCase($getAllProduct);

                return response()->json($converted, 200);
            } else if ($request->query("query") === "info") {
                $aggregation = Product::where('status', 'true')
                    ->count();

                $result = [
                    '_count' => [
                        'id' => $aggregation,
                    ],
                ];

                return response()->json($result, 200);
            } else if ($request->query()) {
                $pagination = getPagination($request->query());
                $getAllProduct = Product::orWhere('name', 'LIKE', '%' . $request->query('key') . '%')
                    ->when($request->query('productSubCategoryId'), function ($query) use ($request) {
                        return $query->whereIn('productSubCategoryId', explode(',', $request->query('productSubCategoryId')));
                    })
                    ->when($request->query('productBrandId'), function ($query) use ($request) {
                        return $query->whereIn('productBrandId', explode(',', $request->query('productBrandId')));
                    })
                    ->when($request->query('color'), function ($query) use ($request) {
                        return $query->whereHas('productColor', function ($query) use ($request) {
                            return $query->whereIn('colorId', explode(',', $request->query('color')));
                        });
                    })
                    ->when($request->query('priceRange'), function ($query) use ($request) {
                        $priceRange = explode('-', $request->query('priceRange'));

                        if (count($priceRange) === 2) {
                            $minPrice = (int) $priceRange[0];
                            $maxPrice = (int) $priceRange[1];

                            return $query->where('productSalePrice', '>=', $minPrice)
                                ->where('productSalePrice', '<=', $maxPrice);
                        }
                        return $query;
                    })
                    ->when($request->query('price'), function ($query) use ($request) {
                        return $query->orderBy('productSalePrice', $request->query('price') === 'LowToHigh' ? 'asc' : 'desc');
                    })
                    ->orderBy('id', 'desc')
                    ->where('status', 'true')
                    ->with('productVat', 'productSubCategory', 'productBrand', 'productColor.color', 'discount', 'galleryImage', 'productProductAttributeValue.productAttributeValue.productAttribute', 'uom:id,name')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $totalProductCount = Product::orWhere('name', 'LIKE', '%' . $request->query('key') . '%')
                    ->when($request->query('productSubCategoryId'), function ($query) use ($request) {
                        return $query->whereIn('productSubCategoryId', explode(',', $request->query('productSubCategoryId')));
                    })
                    ->when($request->query('productBrandId'), function ($query) use ($request) {
                        return $query->whereIn('productBrandId', explode(',', $request->query('productBrandId')));
                    })
                    ->when($request->query('color'), function ($query) use ($request) {
                        return $query->whereHas('productColor', function ($query) use ($request) {
                            return $query->whereIn('colorId', explode(',', $request->query('color')));
                        });
                    })
                    ->when($request->query('priceRange'), function ($query) use ($request) {
                        $priceRange = explode('-', $request->query('priceRange'));

                        if (count($priceRange) === 2) {
                            $minPrice = (int) $priceRange[0];
                            $maxPrice = (int) $priceRange[1];

                            return $query->where('productSalePrice', '>=', $minPrice)
                                ->where('productSalePrice', '<=', $maxPrice);
                        }
                        return $query;
                    })
                    ->when($request->query('price'), function ($query) use ($request) {
                        return $query->orderBy('productSalePrice', $request->query('price') === 'LowToHigh' ? 'asc' : 'desc');
                    })
                    ->where('status', 'true')
                    ->count();

                // remove productPurchasePrice
                $getAllProduct->map(function ($item) {
                    unset ($item->productPurchasePrice);
                    unset ($item->uomValue);
                    unset ($item->purchaseInvoiceId);
                    unset ($item->reorderQuantity);
                    return $item;
                });


                $currentAppUrl = url('/');
                collect($getAllProduct)->map(function ($product) use ($currentAppUrl) {
                    if ($product->productThumbnailImage) {
                        $product->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$product->productThumbnailImage}";
                    }
                    return $product;
                });

                $converted = $this->getKeysToCamelCase($getAllProduct);
                $finalResult = [
                    'getAllProduct' => $converted,
                    'totalProduct' => $totalProductCount,
                ];

                return response()->json($finalResult, 200);
            } else {
                return response()->json(['error' => 'Invalid Query!'], 400);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
        }
    }

    /**
     * @param $getAllProduct
     * @return Collection
     */
    public function getKeysToCamelCase($getAllProduct): Collection
    {
        $converted = arrayKeysToCamelCase($getAllProduct->toArray());

        //get all productSalePriceWithVat
        return collect($converted)->map(function ($item) {
            $vat = ProductVat::where('id', $item['productVatId'])->first();
            $vatPercentage = $vat->percentage ?? 0;

            $item['productSalePriceWithVat'] = $item['productSalePrice'] + ($item['productSalePrice'] * $vatPercentage) / 100;
            return $item;
        });
    }

    public function getSingleProduct($id): JsonResponse
    {
        try {
            $singleProduct = Product::where('id', (int) $id)
                ->with('productVat', 'discount', 'productColor.color',  'productSubCategory.productCategory', 'productBrand', 'galleryImage', 'productProductAttributeValue.productAttributeValue', 'uom:id,name')
                ->with('discount', 'productColor.color', 'productSubCategory.productCategory', 'productBrand', 'galleryImage', 'productProductAttributeValue.productAttributeValue.productAttribute')
                ->first();

            $currentAppUrl = url('/');
            if ($singleProduct->productThumbnailImage) {
                $singleProduct->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$singleProduct->productThumbnailImage}";
            }

            $singleProduct->galleryImage->map(function ($item) use ($currentAppUrl) {
                $item->imageUrl = "{$currentAppUrl}/product-image/{$item->imageName}";
                return $item;
            });

            if (!$singleProduct) {
                return response()->json(['error' => 'product not found!'], 404);
            }




            $converted = arrayKeysToCamelCase($singleProduct->toArray());


            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting product.Please try again later.'], 500);
        }
    }

    public function getSingleProductPublic(Request $request, $id): JsonResponse
    {
        try {
            $currentAppUrl = url('/');
            $singleProduct = Product::where('id', (int) $id)
                ->with('productVat', 'discount', 'productColor.color', 'productSubCategory.productCategory', 'productBrand', 'galleryImage', 'productProductAttributeValue.productAttributeValue.productAttribute', 'uom:id,name')
                ->with('discount', 'productColor.color',  'productSubCategory.productCategory', 'productBrand', 'galleryImage', 'productProductAttributeValue.productAttributeValue.productAttribute')
                ->first();

            if ($singleProduct->productThumbnailImage != null) {
                $singleProduct->productThumbnailImageUrl = "{$currentAppUrl}/product-image/{$singleProduct->productThumbnailImage}";
            }

            $singleProduct->galleryImage->map(function ($item) use ($currentAppUrl) {
                $item->imageUrl = "{$currentAppUrl}/product-image/{$item->imageName}";
                return $item;
            });

            if (!$singleProduct) {
                return response()->json(['error' => 'product not found!'], 404);
            }

          

            $converted = arrayKeysToCamelCase($singleProduct->toArray());

            $data = getCookies($request);
          
            //calculate the sale price with vat
            $vat = ProductVat::where('id', $singleProduct->productVatId)->first();
            $vatPercentage = $vat->percentage ?? 0;

            $converted['productSalePriceWithVat'] = $singleProduct->productSalePrice + ($singleProduct->productSalePrice * $vatPercentage) / 100;

            //unset productPurchasePrice, uomValue,purchaseInvoiceId,reorderQuantity
            unset($converted['productPurchasePrice']);
            unset($converted['uomValue']);
            unset($converted['purchaseInvoiceId']);
            unset($converted['reorderQuantity']);

            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    public function updateSingleProduct(Request $request, $id): JsonResponse
    {
        try {
            if ($request->query('query') === 'update-image') {
                $file_paths = $request->file_paths;
                if (!$file_paths) {
                    return response()->json(['error' => 'No Image Given!'], 404);
                }

                $galleryImages = Images::where('productId', $id)
                    ->where('id', $request->imageId)
                    ->first();
                if (!$galleryImages) {
                    return response()->json(['error' => 'Image not found!'], 404);
                }
                $oldImagePath = 'uploads/' . $galleryImages->imageName;

                if (Storage::exists($oldImagePath)) {
                    Storage::delete($oldImagePath);
                }

                if ($file_paths) {
                    $galleryImages->update([
                        'imageName' => $file_paths[0] ?? $galleryImages->imageName,
                    ]);
                    if (!$galleryImages) {
                        return response()->json(['error' => 'Failed To Updated Product'], 404);
                    }
                }
                return response()->json(['message' => 'Product updated Successfully'], 200);
            } else if ($request->hasFile('images')) {
                $file_paths = $request->file_paths;

                $product = Product::where('id', $id)->first();
                $product->update([
                    'name' => $request->input('name') ?? $product->name,
                    'productThumbnailImage' => $file_paths[0] ?? $product->productThumbnailImage,
                    'productSubCategoryId' => (int) $request->input('productSubCategoryId') ? (int) $request->input('productSubCategoryId') : $product->productSubCategoryId,
                    'productBrandId' => (int) $request->input('productBrandId') ? (int) $request->input('productBrandId') : $product->productBrandId,
                    'description' => $request->input('description') ?? $product->description,
                    'sku' => $request->input('sku') ?? $product->sku,
                    'productSalePrice' => takeUptoThreeDecimal((float) $request->input('productSalePrice')) ?? $product->productSalePrice,
                    'uomId' => $request->input('uomId') ? (int) $request->input('uomId') : $product->uomId,
                    'uomValue' => takeUptoThreeDecimal((float) $request->input('uomValue')) ?? $product->uomValue,
                    'reorderQuantity' => (int) $request->input('reorderQuantity') ?? $product->reorderQuantity,
                    'productVatId' => (int) $request->input('productVatId') ? (int) $request->input('productVatId') : $product->productVatId,
                    'discountId' => (int) $request->input('discountId') ? (int) $request->input('discountId') : $product->discountId,
                ]);

                if (!$product) {
                    return response()->json(['error' => 'Failed To Updated Product'], 404);
                }

                // add color code against createdProduct Id
                $colors = $request->input('colors');
                $colorsArray = explode(',', $colors);
                if ($colors) {
                    ProductColor::where('productId', $id)->delete();
                    foreach ($colorsArray as $item) {
                        ProductColor::create([
                            'productId' => $id,
                            'colorId' => $item,
                        ]);
                    }
                }
                return response()->json(['message' => 'Product updated Successfully'], 200);
            }

            $product = Product::where('id', $id)->first();

            $product->update([
                'name' => $request->input('name') ?? $product->name,
                'productThumbnailImage' => $file_paths[0] ?? $product->productThumbnailImage,
                'productSubCategoryId' => (int) $request->input('productSubCategoryId') ? (int) $request->input('productSubCategoryId') : $product->productSubCategoryId,
                'productBrandId' => (int) $request->input('productBrandId') ? (int) $request->input('productBrandId') : $product->productBrandId,
                'description' => $request->input('description') ?? $product->description,
                'sku' => $request->input('sku') ?? $product->sku,
                'productSalePrice' => takeUptoThreeDecimal((float) $request->input('productSalePrice')) ?? $product->productSalePrice,
                'uomId' => $request->input('uomId') ?? $product->uomId,
                'uomValue' => takeUptoThreeDecimal((float) $request->input('uomValue')) ?? $product->uomValue,
                'reorderQuantity' => (int) $request->input('reorderQuantity') ?? $product->reorderQuantity,
                'productVatId' => (int) $request->input('productVatId') ? (int) $request->input('productVatId') : $product->productVatId,
                'discountId' => (int) $request->input('discountId') ? (int) $request->input('discountId') : $product->discountId,
            ]);

            if (!$product) {
                return response()->json(['error' => 'Failed To Updated Product'], 404);
            }

            // add color code against createdProduct Id
            $colors = $request->input('colors');
            $colorsArray = explode(',', $colors);
            if ($colors) {
                ProductColor::where('productId', $id)->delete();
                foreach ($colorsArray as $item) {
                    ProductColor::create([
                        'productId' => $id,
                        'colorId' => $item,
                    ]);
                }
            }
            return response()->json(['message' => 'Product updated Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during updated product.Please try again later.'], 500);
        }
    }

    public function deleteSingleProduct(Request $request, $id): JsonResponse
    {
        try {
            if ($request->query('query') === 'delete-image') {
                $galleryImages = Images::where('productId', $id)
                    ->where('id', $request->query('imageId'))
                    ->first();
                if (!$galleryImages) {
                    return response()->json(['error' => 'Image not found!'], 404);
                }
                $oldImagePath = 'uploads/' . $galleryImages->imageName;

                if (Storage::exists($oldImagePath)) {
                    Storage::delete($oldImagePath);
                }

                $deletedImage = Images::where('productId', $id)
                    ->where('id', $request->query('imageId'))
                    ->delete();

                if (!$deletedImage) {
                    return response()->json(['error' => 'Failed To Delete Product'], 404);
                }
                return response()->json(['message' => 'Product deleted Successfully'], 200);
            } else {
                $deletedProduct = Product::where('id', (int) $id)
                    ->update([
                        'status' => $request->input('status'),
                    ]);

                if (!$deletedProduct) {
                    return response()->json(['error' => 'Failed To Delete Product'], 404);
                }
                return response()->json(['message' => 'Product deleted Successfully'], 200);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete product.Please try again later.'], 500);
        }
    }
    /**
     * Fast API for products with minimal data and aggressive caching
     */
    public function getAllProductFast(Request $request): JsonResponse
    {
        try {
            if ($request->query('query') === 'all') {
                // Ultra-fast cached response with minimal data
                $cacheKey = 'products_fast_all';
                $products = Cache::remember($cacheKey, 3600, function () { // 1 hour cache
                    return Product::select('id', 'name', 'productQuantity', 'productSalePrice', 'status')
                        ->where('status', 'true')
                        ->orderBy('id', 'desc')
                        ->get();
                });

                return response()->json([
                    'products' => $products,
                    'count' => $products->count(),
                    'cached' => true,
                    'fast' => true
                ], 200);
            }

            if ($request->query('query') === 'info') {
                $cacheKey = 'products_info_fast';
                $result = Cache::remember($cacheKey, 1800, function () { // 30 minutes cache
                    $count = Product::where('status', 'true')->count();
                    return [
                        '_count' => ['id' => $count],
                        'cached' => true,
                        'fast' => true
                    ];
                });

                return response()->json($result, 200);
            }

            return response()->json(['error' => 'Invalid query parameter'], 400);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred: ' . $err->getMessage()], 500);
        }
    }
}

function getCookies($request): array
{
    $secret = config('app.jwt_secret');
    $token = $request->bearerToken();
    if (empty($token) || !$secret) {
        return [];
    } else {
        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            return [];
        }
    }
}
