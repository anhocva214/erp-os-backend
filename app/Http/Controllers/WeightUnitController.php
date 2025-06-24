<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\WeightUnit;
use Illuminate\Http\Request;

class WeightUnitController extends Controller
{
    

    public function createSingleWeightUnit(Request $request)
    {
        try{
            $weightUnit = WeightUnit::where('name', $request->name)->first();
            if($weightUnit){
                return response()->json(['error' => 'A Weight Unit with the same name already exists'], 400);
            }
            $weightUnit = new WeightUnit();
            $weightUnit->name = $request->name;
            $weightUnit->save();
            $converted = arrayKeysToCamelCase($weightUnit->toArray());
            return response()->json($converted, 201);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while creating the Weight Unit record'], 500);
        }
    }


    public function getAllWeightUnit()
    {
        try{
            $weightUnits = WeightUnit::where('status', 'true')->get();
            $converted = arrayKeysToCamelCase($weightUnits->toArray());
            return response()->json($converted, 200);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while retrieving the Weight Units'], 500);
        }
    }

    public function updateSingleWeightUnit(Request $request, $id)
    {
        try{
            $weightUnit = WeightUnit::where('name', $request->name)->first();
            if($weightUnit){
                return response()->json(['error' => 'A Weight Unit with the same name already exists'], 400);
            }
            $weightUnit = WeightUnit::find($id);
            $weightUnit->name = $request->name;
            $weightUnit->save();
            $converted = arrayKeysToCamelCase($weightUnit->toArray());
            return response()->json($converted, 200);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while updating the Weight Unit record'], 500);
        }
    }

    public function deleteSingleWeightUnit(Request $request, $id)
    {
        try{
            $weightUnit = WeightUnit::find($id);
            $weightUnit->status = $request->status;
            $weightUnit->save();
            return response()->json(['message' => 'Weight Unit record deleted successfully'], 200);
        } catch(Exception $e){
            return response()->json(['error' => 'An error occurred while deleting the Weight Unit record'], 500);
        }
    }
    
}
