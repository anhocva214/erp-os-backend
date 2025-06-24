<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Manufacturer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ManufacturerController extends Controller
{
    

    public function createSingleManufacturer(Request $request): JsonResponse
    {
        try{

            $manufacturer = Manufacturer::where('name', $request->name)->first();
            if($manufacturer){
                return response()->json(['error' => 'A Manufacturer with the same name already exists'], 400);
            }
            $manufacturer = new Manufacturer();
            $manufacturer->name = $request->name;
            $manufacturer->save();
            $converted = arrayKeysToCamelCase($manufacturer->toArray());
            return response()->json($converted, 201);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while creating the Manufacturer record'], 500);
        }
    }


    public function getAllManufacturer(): JsonResponse
    {
        try{
            $manufacturers = Manufacturer::where('status', 'true')->get();
            $converted = arrayKeysToCamelCase($manufacturers->toArray());
            return response()->json($converted, 200);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while retrieving the Manufacturers'], 500);
        }
    }
    //get single manufacturer
    public function getSingleManufacturer($id): JsonResponse
    {
        try{
            $manufacturer = Manufacturer::find($id);
            if(!$manufacturer){
                return response()->json(['error' => 'Manufacturer not found'], 404);
            }
            $converted = arrayKeysToCamelCase($manufacturer->toArray());
            return response()->json($converted, 200);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while retrieving the Manufacturer'], 500);
        }
    }


    public function updateSingleManufacturer(Request $request, $id): JsonResponse
    {
        try{
            $manufacturer = Manufacturer::where('name', $request->name)->first();
            if($manufacturer){
                return response()->json(['error' => 'A Manufacturer with the same name already exists'], 400);
            }
            $manufacturer = Manufacturer::find($id);
            $manufacturer->name = $request->name;
            $manufacturer->save();
            $converted = arrayKeysToCamelCase($manufacturer->toArray());
            return response()->json($converted, 200);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while updating the Manufacturer record'], 500);
        }
    }

    public function deleteSingleManufacturer(Request $request, $id): JsonResponse
    {
        try{
            $manufacturer = Manufacturer::find($id);
            $manufacturer->status = $request->status;
            $manufacturer->save();
            return response()->json(['message' => 'Manufacturer record deleted successfully'], 200);
        } catch(Exception $e){
            return response()->json(['error' => 'An error occurred while deleting the Manufacturer record'], 500);
        }
    }
}
