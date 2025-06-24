<?php

namespace App\Http\Controllers;

use App\Models\Colors;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColorsController extends Controller
{
    //create a single colors controller method
    public function createSingleColors(Request $request): JsonResponse
    {
        if ($request->query('query') === 'deletemany') {
            try {
                $ids = json_decode($request->getContent(), true);
                $deletedColors = Colors::destroy($ids);

                $deletedCount = [
                    'count' => $deletedColors
                ];

                return response()->json($deletedCount, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during delete colors. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'createmany') {
            try {
                $colorsData = json_decode($request->getContent(), true);

                $createdColors = collect($colorsData)->map(function ($item) {
                    return Colors::firstOrCreate([
                        'name' => $item['name'],
                        'colorCode' => $item['colorCode'],
                    ]);
                });

                $result = [
                    'count' => count($createdColors),
                ];

                return response()->json($result, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create colors. Please try again later.'], 500);
            }
        } else {
            try {
                $colorsData = json_decode($request->getContent(), true);

                $createdColors = Colors::create([
                    'name' => $colorsData['name'],
                    'colorCode' => $colorsData['colorCode'],
                ]);

                $converted = arrayKeysToCamelCase($createdColors->toArray());
                return response()->json($converted, 201);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during create colors. Please try again later.'], 500);
            }
        }
    }

    // get all colors controller method
    public function getAllColors(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $allColors = Colors::orderBy('id', 'desc')
                    ->where('status', 'true')
                    ->get();

                $converted = arrayKeysToCamelCase($allColors->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting colors. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'info') {
            try {
                $aggregation = Colors::where('status', 'true')
                    ->count();

                $result = [
                    '_count' => [
                        'id' => $aggregation,
                    ],
                ];

                return response()->json($result, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting colors. Please try again later.'], 500);
            }
        } elseif ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());
                $allColors = Colors::
                    where('id', $request->query('key'))
                    ->orWhere('name', 'like', '%' . $request->query('key') . '%')
                    ->orWhere('colorCode', 'like', '%' . $request->query('key') . '%')
                    ->orderBy('id', 'desc')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();
                $totalColors = Colors::
                    where('id', $request->query('status'))
                    ->orWhere('name', 'like', '%' . $request->query('key') . '%')
                    ->orWhere('colorCode', 'like', '%' . $request->query('key') . '%')
                    ->count();
                    

                $converted = arrayKeysToCamelCase($allColors->toArray());
                $finalResult = [
                    'getAllProductColor' => $converted,
                    'totalProductColor' => $totalColors,
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting colors. Please try again later.'], 500);
            }
        }  else if ($request->query()) {
            try{
            $pagination = getPagination($request->query());
            $allColors = Colors::
                when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })
                ->orderBy('id', 'desc')
                ->skip($pagination['skip'])
                ->take($pagination['limit'])
                ->get();
            $totalColors = Colors::
                when($request->query('status'), function ($query) use ($request) {
                    return $query->whereIn('status', explode(',', $request->query('status')));
                })
                ->count();
            $converted = arrayKeysToCamelCase($allColors->toArray());
            $finalResult = [
                'getAllProductColor' => $converted,
                'totalProductColor' => $totalColors,
            ];
            return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting colors. Please try again later.'], 500);
            }

        } else {
            try {
                $pagination = getPagination($request->query());
                $allColors = Colors::orderBy('id', 'desc')
                    ->where('status', 'true')
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->get();

                $converted = arrayKeysToCamelCase($allColors->toArray());
                $finalResult = [
                    'getAllProductColor' => $converted,
                    'totalProductColor' => Colors::where('status', 'true')->count(),
                ];

                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during getting colors. Please try again later.'], 500);
            }
        }
    }

    public function getPublicColors(Request $request): JsonResponse
    {
        try {
            $allColors = Colors::orderBy('id', 'desc')
                ->where('status', 'true')
                ->get();

            $converted = arrayKeysToCamelCase($allColors->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting colors. Please try again later.'], 500);
        }
    }
    // get a single colors controller method
    public function getSingleColors(Request $request, $id): JsonResponse
    {
        try {
            $singleColor = Colors::where('id', $id)
                ->with(['productColor'=> function($query){
                    $query->with('product')->orderBy('id', 'desc');
                }])
                ->first();

            $converted = arrayKeysToCamelCase($singleColor->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during getting colors. Please try again later.'], 500);
        }
    }

    // update a single color controller method
    public function updateSingleColors(Request $request, $id): JsonResponse
    {
        try {
            $updatedColor = Colors::where('id', (int) $id)
                ->update([
                'name' => $request->input('name'),
                'colorCode' => $request->input('colorCode'),
            ]);

            if (!$updatedColor) {
                return response()->json(['error' => 'Failed To update Color'], 404);
            }
            return response()->json(['message' => 'Color updated Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during update colors. Please try again later.'], 500);
        }
    }

    // delete a single color controller method
    public function deleteSingleColors(Request $request, $id): JsonResponse
    {
        try {
            $deletedColor = Colors::where('id', (int) $id)
                ->update([
                'status' => $request->input('status'),
            ]);

            if (!$deletedColor) {
                return response()->json(['error' => 'Failed To Hide Color'], 404);
            }
            return response()->json(['message' => 'Color Hided Successfully'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during delete colors. Please try again later.'], 500);
        }
    }
}
