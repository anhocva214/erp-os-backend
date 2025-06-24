<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\TermsAndCondition;

class TermsAndConditionController extends Controller
{
    

    public function createSingletermsAndCondition(Request $request)
    {
        try{
            $validate = validator($request->all(), [
                "title" => "required|string",
                "subject" => "required|string",
            ]);
            if($validate->fails()){
                return response()->json(["error" => $validate->errors()->first()], 400);
            }
            $termsAndCondition = TermsAndCondition::create([
                "title" => $request->input("title"),
                "subject" => $request->input("subject"),
            ]);

            $converted = arrayKeysToCamelCase($termsAndCondition->toArray());
            return response()->json($converted, 201);
        }
        catch(Exception $e){
            return response()->json(["error" => $e->getMessage(), 500]);
        }
    }

    public function getAlltermsAndCondition(Request $request)
    {
        try{
            if($request->query('query')=== 'all'){
                $termsAndCondition = TermsAndCondition::where('status', 'true')
                ->orderBy('id', 'desc')
                ->get();
                $converted = arrayKeysToCamelCase($termsAndCondition->toArray());
                return response()->json($converted, 200);
            }
            elseif($request->query()){
                $pagination = getPagination($request->query());
                $termsAndCondition = TermsAndCondition::where('status', $request->query('status'))
                ->skip($pagination['skip'])
                ->take($pagination['limit'])
                ->get();

                $converted = arrayKeysToCamelCase($termsAndCondition->toArray());
                return response()->json($converted, 200);
            }
            else{
                return response()->json(["error" => "Invalid query parameter"], 400);   
            }
               
        }
        catch(Exception $e){
            return response()->json(["error" => $e->getMessage(), 500]);
        }
    }

    public function getSingletermsAndCondition(Request $request, $id)
    {
        try{
            $termsAndCondition = TermsAndCondition::findOrFail($id);
            if($termsAndCondition){
                $converted = arrayKeysToCamelCase($termsAndCondition->toArray());
                return response()->json($converted, 200);
            }
            else{
                return response()->json(["error" => "terms and condition not found", 404]);
            }
        }
        catch(Exception $e){
            return response()->json(["error" => $e->getMessage(), 500]);
        }
    }

    public function updateSingletermsAndCondition(Request $request, $id)
    {
        try{
            $validate = validator($request->all(), [
                "title" => "required|string",
                "subject" => "required|string",
            ]);
            if($validate->fails()){
                return response()->json(["error" => $validate->errors()->first()], 400);
            }
            $termsAndCondition = TermsAndCondition::where('id', $id)->get();
            if(!$termsAndCondition){
                return response()->json(["error" => "terms and condition not found", 404]);
            }
            else{
                $termsAndCondition = TermsAndCondition::where('id', $id)->update([
                    "title" => $request->input("title") ?? $termsAndCondition->title,
                    "subject" => $request->input("subject") ?? $termsAndCondition->subject,
                ]);
                return response()->json(["message" => "terms and condition updated successfully"], 200);
            }
        }
        catch(Exception $e){
            return response()->json(["error" => $e->getMessage(), 500]);
        }
    }

    public function deleteSingletermsAndCondition(Request $request, $id)
    {
        try{
            $termsAndCondition = TermsAndCondition::where('id', $id)->get();
            if(!$termsAndCondition){
                return response()->json(["error" => "terms and condition not found", 404]);
            }
            $termsAndCondition = TermsAndCondition::where('id', $id)->update([
                "status" => $request->status,
            ]);
            return response()->json(["message" => "terms and condition deleted successfully"], 200);
        }
        catch(Exception $e){
            return response()->json(["error" => $e->getMessage(), 500]);
        }
    }

}
