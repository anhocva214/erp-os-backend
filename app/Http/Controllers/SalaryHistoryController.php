<?php

namespace App\Http\Controllers;

use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\SalaryHistory;
use Carbon\Carbon;
use Illuminate\Support\Str;

//
class SalaryHistoryController extends Controller
{
    //create salaryHistory controller method
    public function createSingleSalaryHistory(Request $request): jsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedSalaryHistory = SalaryHistory::destroy($ids);
                return response()->json($deletedSalaryHistory, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during deleting many Salary History. Please try again later.'], 500);
            }
        } else if ($request->query('query') === 'createmany') {
            try {
                $salaryHistoryData = json_decode($request->getContent(), true);

                $createdSalaryHistory = collect($salaryHistoryData)->map(function ($item) {

                    $startDate = Carbon::parse($item['salaryStartDate']);
                    $endDate = isset($item['salaryEndDate']) ? Carbon::parse($item['salaryEndDate']) : null;

                    return SalaryHistory::create([
                        'userId' => $item['userId'],
                        'salary' => $item['salary'],
                        'startDate' => $startDate,
                        'endDate' => $endDate ?? null,
                        'comment' => $item['salaryComment'] ?? null,
                    ]);
                });

                $converted = arrayKeysToCamelCase($createdSalaryHistory->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating many Salary History. Please try again later.'], 500);
            }
        } else {
            try {
                $salaryHistoryData = json_decode($request->getContent(), true);

                $startDate = Carbon::parse($salaryHistoryData['salaryStartDate']);
                $endDate = isset($salaryHistoryData['salaryEndDate']) ? Carbon::parse($salaryHistoryData['salaryEndDate']) : null;

                $createdSalaryHistory = SalaryHistory::create([
                    'userId' => $salaryHistoryData['userId'],
                    'salary' => $salaryHistoryData['salary'],
                    'startDate' => $startDate,
                    'endDate' => $endDate ?? null,
                    'comment' => $salaryHistoryData['salaryComment'] ?? null,
                ]);

                $converted = arrayKeysToCamelCase($createdSalaryHistory->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during creating a single Salary History. Please try again later.'], 500);
            }
        }
    }

    // get all the salaryHistory data controller method
    public function getAllSalaryHistory(Request $request): jsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $allSalaryHistory = SalaryHistory::orderBy('id', 'desc')->get();

                $converted = arrayKeysToCamelCase($allSalaryHistory->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting Salary History. Please try again later.'], 500);
            }
        } else if ($request->query('status') === 'false') {
            try {
                $pagination = getPagination($request->query());
                $allSalaryHistory = SalaryHistory::where('status', 'false')->orderBy('id', 'desc')->skip($pagination['skip'])->take($pagination['limit'])->get();

                $converted = arrayKeysToCamelCase($allSalaryHistory->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting Salary History. Please try again later.'], 500);
            }
        } else {
            try {
                $pagination = getPagination($request->query());
                $allSalaryHistory = SalaryHistory::where('status', 'true')->orderBy('id', 'desc')->skip($pagination['skip'])->take($pagination['limit'])->get();

                $converted = arrayKeysToCamelCase($allSalaryHistory->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting Salary History. Please try again later.'], 500);
            }
        }
    }

    // get a single salaryHistory Controller method
    public function getSingleSalaryHistory(Request $request, $id): jsonResponse
    {
        try {
            $singleSalaryHistory = SalaryHistory::with('user:id,firstName,lastName,username,email')->findOrFail($id);

            $converted = arrayKeysToCamelCase($singleSalaryHistory->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting Salary History. Please try again later.'], 500);
        }
    }

    // update a single salaryHistory controller method
    public function updateSingleSalaryHistory(Request $request, $id): jsonResponse
    {
        if ($request->query('query') === 'status') {
            try {
                $updatedSalaryHistoryStatus = SalaryHistory::where('id', $id)->update([
                    'status' => $request->input('status'),
                ]);

                if ($updatedSalaryHistoryStatus) {
                    return response()->json(['message' => 'SalaryHistory Status Change Successfully'], 200);
                }
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during updating Salary History. Please try again later.'], 500);
            }
        } else {
            try {
                $salaryHistoryData = json_decode($request->getContent(), true);

                $startDate = Carbon::parse($salaryHistoryData['salaryStartDate']);
                $endDate = isset($salaryHistoryData['salaryEndDate']) ? Carbon::parse($salaryHistoryData['salaryEndDate']) : null;

                SalaryHistory::where('id', $id)->update([
                    'salary' => $salaryHistoryData['salary'],
                    'startDate' => $startDate,
                    'endDate' => $endDate ?? null,
                    'comment' => $salaryHistoryData['salaryComment'] ?? null,
                ]);

                return response()->json([
                    'message' => 'SalaryHistory Updated Successfully',
                ], 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during updating Salary History. Please try again later.'], 500);
            }
        }
        return response()->json(['error' => 'Invalid Query!'], 400);
    }

    // delete a salaryHistory data controller method
    public function deleteSingleSalaryHistory(Request $request, $id): jsonResponse
    {
        try {
            $deletedSalaryHistory = SalaryHistory::where('id', $id)->delete();

            if ($deletedSalaryHistory) {
                return response()->json(['message' => 'SalaryHistory Deleted Successfully'], 200);
            } else {
                return response()->json(['error' => 'Failed to delete Salary History!'], 404);
            }
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during deleting Salary History. Please try again later.'], 500);
        }
    }
}
