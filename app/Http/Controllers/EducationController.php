<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Education;
use Carbon\Carbon;
use Illuminate\Support\Str;

//
class EducationController extends Controller
{
    //create education controller method
    public function createSingleEducation(Request $request): jsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                // delete many Education at once
                $data = json_decode($request->getContent(), true);
                $deletedEducation = Education::destroy($data);

                $deletedCounted = [
                    'count' => $deletedEducation,
                ];

                return response()->json($deletedCounted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting education. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $educations = $request->json()->all();
                $createdEducation = collect($educations)->map(function ($education) {

                    $startDate = Carbon::parse($education['studyStartDate']);
                    $endDate = isset($education['studyEndDate']) ? Carbon::parse($education['studyEndDate']) : null;

                    return Education::create([
                        'userId' => $education['userId'],
                        'degree' => $education['degree'],
                        'institution' => $education['institution'],
                        'fieldOfStudy' => $education['fieldOfStudy'],
                        'result' => $education['result'],
                        'studyStartDate' => $startDate,
                        'studyEndDate' => $endDate ?? null,
                    ]);
                });

                $converted = arrayKeysToCamelCase($createdEducation->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting education. Please try again later.', $err->getMessage()], 500);
            }
        } else {
            try {

                $startDate = Carbon::parse($request->input('studyStartDate'));
                $endDate = $request->input('studyEndDate') ? Carbon::parse($request->input('studyEndDate')) : null;

                $createdEducation = Education::create([
                    'userId' => $request->input('userId'),
                    'degree' => $request->input('degree'),
                    'institution' => $request->input('institution'),
                    'fieldOfStudy' => $request->input('fieldOfStudy'),
                    'result' => $request->input('result'),
                    'studyStartDate' => $startDate,
                    'studyEndDate' => $endDate ?? null,
                ]);

                $converted = arrayKeysToCamelCase($createdEducation->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting education. Please try again later.'], 500);
            }
        }
    }

    // get all education controller method
    public function getAllEducation(Request $request): jsonResponse
    {

        if ($request->query('query') === 'all') {
            try {
                $allEducation = Education::orderBy('id', 'desc')->get();

                $converted = arrayKeysToCamelCase($allEducation->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting education. Please try again later.'], 500);
            }
        } else if ($request->query('status') === 'false') {
            $pagination = getPagination($request->query());
            try {
                $allEducation = Education::where('status', "false")
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allEducation->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {

                return response()->json(['error' => 'An error occurred during getting education. Please try again later.'], 500);
            }
        } else {

            $pagination = getPagination($request->query());
            try {
                $allEducation = Education::where('status', "true")
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allEducation->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {

                return response()->json(['error' => 'An error occurred during getting education. Please try again later.'], 500);
            }
        }
    }

    // get single education data controller method
    public function getSingleEducation(Request $request, $id): jsonResponse
    {
        try {
            $data = $request->attributes->get('data');

            $singleEducation = Education::findOrFail($id);

            if (($data['sub'] !== $singleEducation->userId) && (!in_array('readAll-eduction', $data['permissions']) || !in_array('readSingle-education', $data['permissions']))) {
                return response()->json(['error' => 'unauthorized!'], 401);
            }

            $converted = arrayKeysToCamelCase($singleEducation->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting single education. Please try again later.'], 500);
        }
    }

    // update single education controller method
    public function updateSingleEducation(Request $request, $id): jsonResponse
    {
        if ($request->query('query') === 'status') {
            try {
                $updatedEducationStatus = Education::where('id', $id)->update([
                    'status' => $request->input('status')
                ]);

                if ($updatedEducationStatus) {
                    return response()->json(['message' => 'Education Status Change Successfully'], 200);
                } else {
                    return response()->json(['error' => 'Failed to update education status!'], 404);
                }
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during updating education. Please try again later.'], 500);
            }
        } else {
            try {
                //2018-09-01 00:00:00
                $startDate = Carbon::parse($request->input('studyStartDate'));
                $endDate = $request->input('studyEndDate') ? Carbon::parse($request->input('studyEndDate')) : null;

                $updatedEducation = Education::where('id', $id)->update(
                    [
                        'degree' => $request->input('degree'),
                        'institution' => $request->input('institution'),
                        'fieldOfStudy' => $request->input('fieldOfStudy'),
                        'result' => $request->input('result'),
                        'studyStartDate' => $startDate,
                        'studyEndDate' => $endDate ?? null,
                    ]
                );

                if (!$updatedEducation) {
                    return response()->json(['error' => 'Failed to update education!'], 404);
                }
                return response()->json(['message' => 'Education updated successfully'], 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during updating education. Please try again later.'], 500);
            }
        }
    }

    // delete single education controller method
    public function deleteSingleEducation(Request $request, $id): jsonResponse
    {
        try {
            $deletedEducation = Education::where('id', (int)$id)->delete();

            if ($deletedEducation) {
                return response()->json(['message' => 'Education Deleted Successfully'], 200);
            } else {
                return response()->json(['error' => 'Failed To Delete Education'], 404);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during deleting education. Please try again later.'], 500);
        }
    }
}
