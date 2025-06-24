<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\ProductAttributeValue;

class ProductAttributeValueController extends Controller
{
    

    public function createSingleProductAttributeValue(Request $request)
    {
        if ($request->query('query') === 'deletemany') {
            try {

                $data = json_decode($request->getContent(), true);
                $deletedProductAttributeValue = ProductAttributeValue::destroy($data);

                $deletedCounted = [
                    'count' => $deletedProductAttributeValue,
                ];
                return response()->json($deletedCounted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during deleting many ProductAttributeValue. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $productAttributeValueData = $request->json()->all();
                $createdProductAttributeValue = collect($productAttributeValueData)->map(function ($productAttributeValue) {
                    return ProductAttributeValue::create([
                        'productAttributeId' => $productAttributeValue['productAttributeId'],
                        'name' => $productAttributeValue['name'],
                    ]);
                });

                $converted = arrayKeysToCamelCase($createdProductAttributeValue->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating many ProductAttributeValue. Please try again later.'], 500);
            }
        } else {
            try {

                $createdProductAttributeValue = ProductAttributeValue::create([
                    'productAttributeId' => $request->input('productAttributeId'),
                    'name' => $request->input('name'),
                ]);
                $converted = arrayKeysToCamelCase($createdProductAttributeValue->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating ProductAttributeValue. Please try again later.'], 500);
            }
        }
    }
    public function getAllProductAttributeValue(Request $request)
    {
        if ($request->query('query') === 'all') {
            try {
                $productAttributeValue = ProductAttributeValue::where('status', 'true')->get();
                $converted = arrayKeysToCamelCase($productAttributeValue->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting all ProductAttributeValue. Please try again later
                .'], 500);}
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $productAttributeValue = ProductAttributeValue::with(['productAttribute'])->where('status', $request->query('status'))
                ->orderBy('id', 'desc')
                ->skip($pagination['skip'])
                ->take($pagination['limit'])
                ->get();

                $total = ProductAttributeValue::where('status', $request->query('status'))->count();

                $converted = arrayKeysToCamelCase($productAttributeValue->toArray());

                $finalResult = [
                    'getAllProductAttributeValue' => $converted,
                    'totalProductAttributeValue' => $total,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductAttributeValue. Please try again later.'], 500);
            }
        }
    }

    public function getSingleProductAttributeValue(Request $request, $id)
    {
        try {
                $productAttributeValue = ProductAttributeValue::with(['productAttribute'])->where('id', $id)->get();
                $converted = arrayKeysToCamelCase($productAttributeValue->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting ProductAttributeValue. Please try again later.'], 500);
            }
    }

    public function updateSingleProductAttributeValue(Request $request, $id)
    {
        try {
            $productAttributeValue = ProductAttributeValue::findOrFail($id);
            $productAttributeValue->update([
                'name' => $request->input('name')?? $productAttributeValue->name,
            ]);

            $converted = arrayKeysToCamelCase($productAttributeValue->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during updating ProductAttributeValue. Please try again later.'], 500);
        }
    }

    public function deleteSingleProductAttributeValue(Request $request, $id)
    {
        try {
            $productAttributeValue = ProductAttributeValue::where('id', $id)->update([
                'status' => $request->input('status'),
            ]);
            if(!$productAttributeValue){
                return response()->json(['error' => 'An error occurred during deleting ProductAttributeValue. Please try again later.'], 500);
            }
            return response()->json(['success' => 'ProductAttributeValue deleted successfully.'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during deleting ProductAttributeValue. Please try again later.'], 500);
        }
    }
}
