<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Support\Str;

//
class ShiftController extends Controller
{
    //crate a shift controller method
    public function createShift(Request $request): jsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                // delete many shift at once
                $data = json_decode($request->getContent(), true);
                $deletedShift = Shift::destroy($data);

                $deletedCounted = [
                    'count' => $deletedShift,
                ];
                return response()->json($deletedCounted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during deleting many Shift. Please try again later.'], 500);
            }
        }else if ($request->query('query') === 'createmany') {
            try {
                $shiftData = $request->json()->all();
                $createdShift = collect($shiftData)->map(function ($shift) {
                    return Shift::create([
                        'name' => $shift['name'],
                        'startTime' => Carbon::parse($shift['startTime']),
                        'endTime' => Carbon::parse($shift['endTime']),
                    ]);
                });

                $converted = arrayKeysToCamelCase($createdShift->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating many Shift. Please try again later.'], 500);
            }
        } else {
            $startTime = new DateTime($request->input('startTime'));
            $endTime = new DateTime($request->input('endTime'));

            $interval = $endTime->diff($startTime);
            $workHour = $interval->h + ($interval->days * 24);
            if ($workHour < 0) {
                $workHour = 24 + $workHour;
            }

            try {
                $createdShift = Shift::create([
                    'name' => $request->input('name'),
                    'startTime' => new DateTime($request->input('startTime')),
                    'endTime' => new DateTime($request->input('endTime')),
                    'workHour' => $workHour,
                ]);

                $converted = arrayKeysToCamelCase($createdShift->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating a single Shift. Please try again later.'], 500);
            }
        }
    }

    // get all shift data controller method
    public function getAllShift(Request $request): jsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $allShift = Shift::orderBy('id', 'desc')
                    ->where('status', "true")
                    ->get();

                $converted = arrayKeysToCamelCase($allShift->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting Shift. Please try again later.'], 500);
            }
        } else if ($request->query('status') === "false") {
            $pagination = getPagination($request->query());
            try {
                $allShift = Shift::orderBy('id', 'desc')
                    ->where('status', "false")
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allShift->toArray());
                $aggregation = [
                    'getAllShift' => $converted,
                    'totalShift' => Shift::where('status', 'false')->count(),
                ];

                return response()->json($aggregation, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting Shift. Please try again later.'], 500);
            }
        } else if ($request->query()) {
            $pagination = getPagination($request->query());
            try {
                $allShift = Shift::orderBy('id', 'desc')
                    ->when($request->query('status'), function ($query) use ($request) {
                        return $query->whereIn('status', explode(',', $request->query('status')));
                    })
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allShift->toArray());
                $aggregation = [
                    'getAllShift' => $converted,
                    'totalShift' => Shift::where('status', 'true')->count(),
                ];

                return response()->json($aggregation, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting Shift. Please try again later.'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid Query!'], 400);
        }
    }

    // get a single shift data controller method
    public function getSingleShift(Request $request, $id): jsonResponse
    {
        try {
            $singleShift = Shift::with(['user'])
                ->findOrFail($id);

            $usersData = $singleShift->user->map(function ($user) {
                return [
                    'id' => $user->id,
                    'firstName' => $user->firstName,
                    'lastName' => $user->lastName,
                    'username' => $user->username,
                ];
            });
            $singleShift->setRelation('user', $usersData);

            $converted = arrayKeysToCamelCase($singleShift->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting Shift. Please try again later.'], 500);
        }
    }

    // update a single shift data controller method
    public function updateSingleShift(Request $request, $id): jsonResponse
    {
        $startTime = new DateTime($request->input('startTime'));
        $endTime = new DateTime($request->input('endTime'));

        $interval = $endTime->diff($startTime);
        $workHour = $interval->h + ($interval->days * 24);
        if ($workHour < 0) {
            $workHour = 24 + $workHour;
        }

        try {
            $updatedShift = Shift::findOrFail($id);
            $updatedShift->update([
                'name' => $request->input('name'),
                'startTime' => $startTime,
                'endTime' => $endTime,
                'workHour' => $workHour,
            ]);

            $converted = arrayKeysToCamelCase($updatedShift->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during updating Shift. Please try again later.', $err->getMessage()], 500);
        }
    }

    // delete a single shift data controller method
    public function deleteSingleShift(Request $request, $id): jsonResponse
    {
        try {
            $deletedShift = Shift::where('id', (int)$id)->update(['status' => $request->input('status')]);


            if ($deletedShift) {
                return response()->json(['message' => 'Shift Hided Successfully'], 200);
            } else {
                return response()->json(['error' => 'Failed to Hide shift!'], 404);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during deleting Shift. Please try again later.'], 500);
        }
    }
}
