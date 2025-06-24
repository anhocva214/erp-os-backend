<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\jsonResponse;
use Exception;
use Illuminate\Support\Str;
use App\Models\DesignationHistory;
use Carbon\Carbon;

//
class DesignationHistoryController extends Controller
{
    //create designationHistory controller method
    public function createSingleDesignationHistory(Request $request): jsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                $data = json_decode($request->getContent(), true);
                // delete many designation History at a time
                $deletedDesignationHistory = DesignationHistory::destroy($data);

                $deletedCounted = [
                    'count' => $deletedDesignationHistory,
                ];

                return response()->json($deletedCounted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during delete designationHistory. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $HistoryData = json_decode($request->getContent(), true);

                $createdDesignationHistory = collect($HistoryData)->map(function ($designationHistory) {
                    $startDate = Carbon::parse($designationHistory['designationStartDate']);
                    $endDate = isset($designationHistory['designationEndDate']) ? Carbon::parse($designationHistory['designationEndDate']) : null;

                    return DesignationHistory::create([
                        'userId' => $designationHistory['userId'],
                        'designationId' => $designationHistory['designationId'],
                        'startDate' => $startDate,
                        'endDate' => $endDate ?? null,
                        'comment' => $designationHistory['designationComment'] ?? null,
                    ]);
                });

                $converted = arrayKeysToCamelCase($createdDesignationHistory->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create designationHistory. Please try again later.'], 500);
            }
        } else {
            try {

                //do not set the end date automatically

                $startDate = Carbon::parse($request->input('designationStartDate'));
                $endDate = $request->input('designationEndDate') ? Carbon::parse($request->input('designationEndDate')) : null;

                $createdDesignationHistory = DesignationHistory::create([
                    'userId' => $request->input('userId'),
                    'designationId' => $request->input('designationId'),
                    'startDate' => $startDate,
                    'endDate' => $endDate ?? null,
                    'comment' => $request->input('designationComment') ?? null,
                ]);

                $converted = arrayKeysToCamelCase($createdDesignationHistory->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create designationHistory. Please try again later.'], 500);
            }
        }
    }

    // get all the designation History controller method
    public function getAllDesignationHistory(Request $request): jsonResponse
    {
        try {
            $allDesignationHistory = DesignationHistory::orderBy('id', 'desc')->get();

            $converted = arrayKeysToCamelCase($allDesignationHistory->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting designationHistory. Please try again later.'], 500);
        }
    }

    // get a single designation History Controller method
    public function getSingleDesignationHistory(Request $request, $id): jsonResponse
    {
        try {
            $singleDesignationHistory = DesignationHistory::where('id', $id)->orderBy('id', 'desc')->get();

            $converted = arrayKeysToCamelCase($singleDesignationHistory->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting single designationHistory. Please try again later.'], 500);
        }
    }

    // update a single designation history controller method
    public function updateSingleDesignationHistory(Request $request, $id): jsonResponse
    {
        try {
            $startDate = Carbon::parse($request->input('designationStartDate'));
            $endDate = $request->input('designationEndDate') ? Carbon::parse($request->input('designationEndDate')) : null;

            $updatedDesignationHistory = DesignationHistory::where('id', $id)->update([
                'designationId' => $request->input('designationId'),
                'startDate' => $startDate,
                'endDate' => $endDate ?? null,
                'comment' => $request->input('designationComment'),
            ]);

            if (!$updatedDesignationHistory) {
                return response()->json(['error' => 'Failed To Update DesignationHistory'], 404);
            }
            return response()->json(['message' => 'DesignationHistory updated successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during update designationHistory. Please try again later.'], 500);
        }
    }

    // delete a designation history controller method
    public function deleteSingleDesignationHistory(Request $request, $id): jsonResponse
    {
        try {
            $deletedDesignationHistory = DesignationHistory::where('id', $id)->delete();

            if ($deletedDesignationHistory) {
                return response()->json(['message' => 'DesignationHistory Deleted Successfully'], 200);
            } else {
                return response()->json(['error' => 'Failed To Delete DesignationHistory'], 404);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during deleting designationHistory. Please try again later.'], 500);
        }
    }
}
