<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\AwardHistory;
use Illuminate\Support\Str;

//
class AwardHistoryController extends Controller
{

    // single award insert controller method
    public function createSingleAwardHistory(Request $request): jsonResponse
    {

        if ($request->query('query') === 'deletemany') {
            try {
                // delete many Award history at once
                $data = json_decode($request->getContent(), true);
                $deletedAwardHistory = AwardHistory::destroy($data);

                $deletedCounted = [
                    'count' => $deletedAwardHistory,
                ];
                return response()->json($deletedCounted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during delete awardHistory. Please try again later.'], 500);
            }
        } else {
            try {
                $createdAwardHistory = AwardHistory::create([
                    'userId' => $request->input('userId'),
                    'awardId' => $request->input('awardId'),
                    'awardedDate' => new DateTime($request->input('awardedDate')),
                    'comment' => $request->input('comment'),
                ]);

                $converted = arrayKeysToCamelCase($createdAwardHistory->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create awardHistory. Please try again later.'], 500);
            }
        }
    }

    // get all awardHistory Data controller method
    public function getAllAwardHistory(Request $request): jsonResponse
    {
        try {
            $allAwardHistory = AwardHistory::orderBy('id', 'desc')->get();

            $converted = arrayKeysToCamelCase($allAwardHistory->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting Award History. Please try again later.'], 500);
        }
    }

    // get a single awardHistory Data controller method
    public function getSingleAwardHistory(Request $request, $id): jsonResponse
    {
        try {
            $SingleAwardHistory = AwardHistory::findOrFail($id);

            $converted = arrayKeysToCamelCase($SingleAwardHistory->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting single awardHistory. Please try again later.'], 500);
        }
    }

    // update a awardHistory data controller method
    public function updateSingleAwardHistory(Request $request, $id): jsonResponse
    {
        try {
            $updatedAwardHistory = AwardHistory::where('id', $id)->update([
                'awardId' => $request->input('awardId'),
                'awardedDate' => new DateTime($request->input('awardedDate')),
                'comment' => $request->input('comment'),
            ]);

            if (!$updatedAwardHistory) {
                return response()->json(['error' => 'Failed to update Award History!'], 404);
            }
            return response()->json(['message' => 'AwardHistory updated successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during update awardHistory. Please try again later.'], 500);
        }
    }

    // delete a single awardHistory controller method
    public function deleteSingleAwardHistory(Request $request, $id): jsonResponse
    {
        try {
            $deletedAwardHistory = AwardHistory::where('id', (int)$id)->delete();
            if ($deletedAwardHistory) {
                return response()->json(['message' => 'AwardHistory Deleted Successfully'], 200);
            } else {
                return response()->json(['error' => 'Failed To Delete AwardHistory'], 404);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete awardHistory. Please try again later.'], 500);
        }
    }
}
