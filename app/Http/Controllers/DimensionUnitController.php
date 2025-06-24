<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\DimensionUnit;

class DimensionUnitController extends Controller
{
    public function createSingleDimensionUnit(Request $request)
    {
        try{
            $dimensionUnit = DimensionUnit::where('name', $request->name)->first();
            if($dimensionUnit){
                return response()->json(['error' => 'A Dimension Unit with the same name already exists'], 400);
            }
            $dimensionUnit = new DimensionUnit();
            $dimensionUnit->name = $request->name;
            $dimensionUnit->save();
            $converted = arrayKeysToCamelCase($dimensionUnit->toArray());
            return response()->json($converted, 201);
        }catch(Exception $e){
            echo $e;
            return response()->json(['error' => 'An error occurred while creating the Dimension Unit record'], 500);
        }
    }


    public function getAllDimensionUnit()
    {
        try{
            $dimensionUnits = DimensionUnit::where('status', 'true')->get();
            $converted = arrayKeysToCamelCase($dimensionUnits->toArray());
            return response()->json($converted, 200);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while retrieving the Dimension Units'], 500);
        }
    }

    public function updateSingleDimensionUnit(Request $request, $id)
    {
        try{
            $dimensionUnit = DimensionUnit::where('name', $request->name)->first();
            if($dimensionUnit){
                return response()->json(['error' => 'A Dimension Unit with the same name already exists'], 400);
            }
            $dimensionUnit = DimensionUnit::find($id);
            $dimensionUnit->name = $request->name;
            $dimensionUnit->save();
            $converted = arrayKeysToCamelCase($dimensionUnit->toArray());
            return response()->json($converted, 200);
        }catch(Exception $e){
            return response()->json(['error' => 'An error occurred while updating the Dimension Unit record'], 500);
        }
    }

    public function deleteSingleDimensionUnit(Request $request, $id)
    {
        try{
            $dimensionUnit = DimensionUnit::find($id);
            $dimensionUnit->status = $request->status;
            $dimensionUnit->save();
            return response()->json(['message' => 'Dimension Unit record deleted successfully'], 200);
        } catch(Exception $e){
            return response()->json(['error' => 'An error occurred while deleting the Dimension Unit record'], 500);
        }
    }
    
}
