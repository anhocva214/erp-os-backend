<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\EmploymentStatus;
use Illuminate\Support\Str;
//
class EmploymentStatusController extends Controller
{
    //create employmentStatus controller method
    public function createSingleEmployment(Request $request): jsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                $data = json_decode($request->getContent(), true);
                $deletedEmploymentStatus = EmploymentStatus::destroy($data);

                $deletedCounted = [
                    'count' => $deletedEmploymentStatus,
                ];

                return response()->json($deletedCounted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during deleting employmentStatus. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $employmentHistoryData = json_decode($request->getContent(), true);

                $createdEmploymentHistory = collect($employmentHistoryData)->map(function ($item) {
                    return EmploymentStatus::create([
                        'name' => $item['name'],
                        'colourValue' => $item['colourValue'],
                        'description' => $item['description'],
                    ]);
                });

                $converted = arrayKeysToCamelCase($createdEmploymentHistory->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create employmentStatus. Please try again later.'], 500);
            }
        } else {
            try {
                $employmentHistoryData = json_decode($request->getContent(), true);

                $createdEmploymentHistory = EmploymentStatus::create([
                    'name' => $employmentHistoryData['name'],
                    'colourValue' => $employmentHistoryData['colourValue'],
                    'description' => $employmentHistoryData['description'] ?? null,
                ]);

                $converted = arrayKeysToCamelCase($createdEmploymentHistory->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create employmentStatus. Please try again later.'], 500);
            }
        }
    }

    // get all the employmentStatus controller method
    public function getAllEmployment(Request $request): jsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $allEmploymentStatus = EmploymentStatus::orderBy('id', 'desc')
                    ->where('status', "true")
                    ->get();

                $converted = arrayKeysToCamelCase($allEmploymentStatus->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting employmentStatus. Please try again later.'], 500);
            }
        } else if ($request->query('status') === 'false') {
            try {
                $pagination = getPagination($request->query());
                $allEmploymentStatus = EmploymentStatus::orderBy('id', 'desc')
                    ->where('status', "false")
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allEmploymentStatus->toArray());
                $aggregation = [
                    'getAllEmploymentStatus' => $converted,
                    'totalEmploymentStatus' => EmploymentStatus::where('status', 'false')->count(),
                ];

                return response()->json($aggregation, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting employmentStatus. Please try again later.'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $allEmploymentStatus = EmploymentStatus::orderBy('id', 'desc')
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allEmploymentStatus->toArray());
                $aggregation = [
                    'getAllEmploymentStatus' => $converted,
                    'totalEmploymentStatus' => EmploymentStatus::where('status', 'true')->count(),
                ];

                return response()->json($aggregation, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting employmentStatus. Please try again later.'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid Query'], 400);
        }
    }

    // get a single employmentStatus controller method
    public function getSingleEmployment(Request $request, $id): jsonResponse
    {
        try {
            $singleEmploymentStatus = EmploymentStatus::with('user')->findOrFail($id);

            // get specific users data filed
            $usersData = $singleEmploymentStatus->user->map(function ($user) {
                return [
                    'id' => $user->id,
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'username' => $user->username,
                ];
            });
            $singleEmploymentStatus->setRelation('user', $usersData);

            $converted = arrayKeysToCamelCase($singleEmploymentStatus->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting employmentStatus. Please try again later.'], 500);
        }
    }

    // update a employmentStatus controller method
    public function updateSingleEmployment(Request $request, $id): jsonResponse
    {
        try {
            $employmentStatus = EmploymentStatus::findOrFail($id);
            $employmentStatus->update($request->all());

            if(!$employmentStatus) {
                return response()->json(['error' => 'Failed to update employmentStatus.'], 404);
            }else {
                return response()->json(['message' => 'EmploymentStatus updated successfully.'], 200);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during update employmentStatus. Please try again later.'], 500);
        }
    }

    // delete a employmentStatus controller method
    public function deletedEmployment(Request $request, $id): jsonResponse
    {
        try {
            $deletedEmploymentStatus = EmploymentStatus::where('id', $id)->update([
                'status' => $request->input('status'),
            ]);

            if ($deletedEmploymentStatus) {
                return response()->json(['message' => 'EmploymentStatus Change Successfully'], 200);
            } else {
                return response()->json(['error' => 'Failed to Delete EmploymentStatus'], 404);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during deleting employmentStatus. Please try again later.'], 500);
        }
    }
}
