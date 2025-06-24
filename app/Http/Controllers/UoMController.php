<?php

namespace App\Http\Controllers;

use App\Models\UoM;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UoMController extends Controller
{
    /**
     * Create a new uom.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createSingleUoM(Request $request): JsonResponse
    {
        try {

            $uom = new UoM();
            $uom->name = $request->name;
            $uom->save();
            $converted = arrayKeysToCamelCase($uom->toArray());
            return response()->json([$converted], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all uom.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllUoM(Request $request): JsonResponse
    {
        try {
            if ($request->query('query') === 'all') {
                $uom = UoM::where('status', 'true')
                    ->orderBy('id', 'desc')
                    ->get();
                $converted = arrayKeysToCamelCase($uom->toArray());
                return response()->json($converted);
            } elseif ($request->query()) {
                $pagination = getPagination($request->query());
                $uom = UoM::where('status', $request->query('status'))
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->orderBy('id', 'desc')
                    ->get();
                $total = UoM::where('status', $request->query('status'))
                    ->count();
                $converted = arrayKeysToCamelCase($uom->toArray());
                $result = [
                    'getAllUoM' => $converted,
                    'totalUoM' => $total
                ];

                return response()->json($result);
            } else {
                return response()->json(['error' => "Invalid query parameter"], 404);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get single uom.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSingleUoM(Request $request): JsonResponse
    {
        try {
            $uom = UoM::findOrFail($request->id);
            if ($uom) {
                $converted = arrayKeysToCamelCase($uom->toArray());
                return response()->json($converted, 200);
            } else {
                return response()->json(['error' => "UoM not found"], 404);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update single uom.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateSingleUoM(Request $request, $id): JsonResponse
    {
        try {
            $uom = UoM::findOrFail($id);
            $uom->name = $request->name ?? $uom->name;
            $uom->save();
            $converted = arrayKeysToCamelCase($uom->toArray());
            return response()->json($converted, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete single uom.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function deleteSingleUoM(Request $request, $id): JsonResponse
    {
        try {
           $uom = UoM::where('id', $id)->update($request->all());

           if(!$uom) {
               return response()->json(['error' => 'Failed to hide uom.'], 404);
           } else {
               return response()->json(['message' => 'UoM hided successfully.'], 200);
           }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


}
