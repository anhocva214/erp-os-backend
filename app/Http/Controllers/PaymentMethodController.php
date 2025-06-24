<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Carbon\Exceptions\Exception;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function createPaymentMethod(Request $request): JsonResponse
    {
        try {
            $file_paths = $request->file_paths;
            $paymentMethod = PaymentMethod::create([
                'subAccountId' => $request->input('subAccountId'),
                'methodName' => $request->input('methodName'),
                'logo' => $file_paths[0] ?? null,
                'ownerAccount' => $request->input('ownerAccount') ?? null,
                'instruction' => $request->input('instruction') ?? null,
            ]);
            $converted = arrayKeysToCamelCase($paymentMethod->toArray());
            return response()->json($converted, 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during create Payment Method!'], 500);
        }
    }

    public function getAllPaymentMethods(Request $request): JsonResponse
    {
        if ($request->query('query') === 'all') {
            try {
                $data = $request->attributes->get('data');
                if ($data['role'] === 'admin') {
                    $paymentMethods = PaymentMethod::with('subAccount')
                    ->orderBy('id', 'desc')
                    ->get();
                } else {
                    $paymentMethods = PaymentMethod::with('subAccount')
                        ->where('status', 'true')
                        ->where('isActive', 'true')
                        ->orderBy('id', 'desc')
                        ->get();
                }
                $converted = arrayKeysToCamelCase($paymentMethods->toArray());
                return response()->json($converted, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during Payment Method fetch!'], 500);
            }
        } else if ($request->query('query') === 'search') {
            try {
                $pagination = getPagination($request->query());
                $paymentMethods = PaymentMethod::with('subAccount')->where('methodName', 'LIKE', "%{$request->query('key')}%")
                    ->orWhere('subAccountId', 'LIKE', "%{$request->query('key')}%")
                    ->orWhere('isActive', 'LIKE', "%{$request->query('key')}%")
                    ->orWhere('status', 'LIKE', "%{$request->query('key')}%")
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->orderBy('id', 'desc')
                    ->get();

                $total = PaymentMethod::where('methodName', 'LIKE', "%{$request->query('key')}%")
                    ->orWhere('subAccountId', 'LIKE', "%{$request->query('key')}%")
                    ->orWhere('ownerAccount', 'LIKE', "%{$request->query('key')}%")
                    ->orWhere('isActive', 'LIKE', "%{$request->query('key')}%")
                    ->orWhere('status', 'LIKE', "%{$request->query('key')}%")
                    ->count();

                $converted = arrayKeysToCamelCase($paymentMethods->toArray());

                $finalResult = [
                    'getAllPaymentMethod' => $converted,
                    'totalPaymentMethod' => $total,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during Payment Method fetch'], 500);
            }
        } else if ($request->query()) {
            try {
                $pagination = getPagination($request->query());
                $paymentMethods = PaymentMethod::with('subAccount')
                    ->where('status', $request->query('status'))
                    ->skip($pagination['skip'])
                    ->take($pagination['limit'])
                    ->orderBy('id', 'desc')
                    ->get();

                $total = PaymentMethod::where('status', $request->query('status'))
                    ->count();

                $converted = arrayKeysToCamelCase($paymentMethods->toArray());

                $finalResult = [
                    'getAllPaymentMethod' => $converted,
                    'totalPaymentMethod' => $total,
                ];
                return response()->json($finalResult, 200);
            } catch (Exception $err) {
                return response()->json(['error' => 'An error occurred during Payment Method fetch'], 500);
            }
        } else {
            return response()->json(['error' => 'Invalid query!'], 409);
        }
    }


    public function updatePaymentMethod(Request $request, $id): JsonResponse
    {
        try {
            $file_paths = $request->file_paths;
            if ($id == 1) {
                if ($request->input('methodName') || $request->input('ownerAccount') || $request->input('instruction') || $request->input('subAccountId')) {
                    return response()->json(['error' => 'This payment method has update restrictions!'], 500);
                }
                $paymentMethod = PaymentMethod::where('id', $id)->first();
                $updated = PaymentMethod::where('id', $id)->update([
                    'logo' => $file_paths[0] ?? $paymentMethod->logo,
                ]);
                if (!$updated) {
                    return response()->json(['error' => 'An error occurred during Payment Method update!'], 500);
                }
                return response()->json(['message' => 'Payment Method Update Successful!'], 200);
            }

            $paymentMethod = PaymentMethod::where('id', $id)->first();
            if ($request->has('images')) {
                $updated = PaymentMethod::where('id', $id)->update([
                    'methodName' => $request->input('methodName') ?? $paymentMethod->methodName,
                    'logo' => $file_paths[0] ?? $paymentMethod->logo,
                    'ownerAccount' => $request->input('ownerAccount') ?? $paymentMethod->ownerAccount,
                    'instruction' => $request->input('instruction') ?? $paymentMethod->instruction,
                    'subAccountId' => $request->input('subAccountId') ?? $paymentMethod->subAccountId,
                ]);
                if (!$updated) {
                    return response()->json(['error' => 'An error occurred during Payment Method update!'], 500);
                }
                return response()->json(['message' => 'Payment Method Update Successful!'], 200);
            }

            if ($request->input('isActive')) {
                $updated = PaymentMethod::where('id', $id)->update([
                    'isActive' => $request->input('isActive'),
                ]);

                if (!$updated) {
                    return response()->json(['error' => 'An error occurred during Payment Method update!'], 500);
                }

                return response()->json(['message' => 'Payment Method Update Successful!'], 200);
            }

            $updated = PaymentMethod::where('id', $id)->update($request->all());

            if (!$updated) {
                return response()->json(['error' => 'An error occurred during Payment Method update!'], 500);
            }
            return response()->json(['message' => 'Payment Method Update Successful!'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during Payment Method update'], 500);
        }
    }

    public function deletePaymentMethod(Request $request, $id): JsonResponse
    {
        try {
            if ($id == 1) {
                return response()->json(['error' => 'This payment method has removal restrictions!'], 500);
            }
            $paymentMethod = PaymentMethod::where('id', $id)->update([
                'status' => $request->input('status'),
            ]);

            if (!$paymentMethod) {
                return response()->json(['error' => 'An error occurred during Payment Method delete!'], 500);
            }
            return response()->json(['message' => 'Payment Method deleted successfully!'], 200);
        } catch (Exception $err) {
            return response()->json(['error' => 'An error occurred during Deleting Payment Method '], 500);
        }
    }
}
