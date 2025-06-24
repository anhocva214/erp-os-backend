<?php

namespace App\Http\Controllers;
//

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\SaleInvoice;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function getDashboardData(Request $request): JsonResponse
    {
        try {
            // Cache app settings
            $appData = Cache::remember('app_settings', 3600, function () {
                return AppSetting::first();
            });

            if (!$appData) {
                return response()->json(['error' => 'App settings not found'], 404);
            }
            if (!in_array($appData->dashboardType, ['inventory', 'e-commerce', 'both'])) {
                return response()->json(['error' => 'Invalid dashboard type'], 400);
            }

            // Create cache key based on request parameters
            $cacheKey = 'dashboard_' . $appData->dashboardType . '_' .
                       ($request->query('startDate') ?? 'all') . '_' .
                       ($request->query('endDate') ?? 'all');

            $result = Cache::remember($cacheKey, 600, function () use ($request, $appData) { // 10 minutes cache
                return $this->calculateDashboardData($request, $appData);
            });

            return response()->json($result, 200);
        } catch (Exception $err) {
            return response()->json(['error' => $err->getMessage()], 500);
        }
    }

    private function calculateDashboardData($request, $appData) {

            if ($appData->dashboardType === 'inventory') {

                $allSaleInvoices = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(paidAmount) as paidAmount, SUM(dueAmount) as dueAmount, SUM(profit) as profit, date')
                    ->get();

                // $totalSaleInvoice = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                //     return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                //         ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                // })
                //     ->groupBy('date')
                //     ->orderBy('date', 'desc')
                //     ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount,SUM(paidAmount) as paidAmount, SUM(dueAmount) as dueAmount, SUM(profit) as profit, date')
                //     ->count();

                $totalSaleInvoice = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })->count();

                $allPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount,SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, date')
                    ->get();

                // $totalPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                //     return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                //         ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                // })
                //     ->groupBy('date')
                //     ->orderBy('date', 'desc')
                //     ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount,SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, date')
                //     ->count();

                $totalPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                });

                $totalPurchaseInvoice = $totalPurchaseInvoice->count();


                //total sale and total purchase amount is calculated by subtracting total discount from total amount (saiyed)
                $cartInfo = [
                    'totalSaleInvoice' => $totalSaleInvoice,
                    'totalSaleAmount' => $allSaleInvoices->sum('totalAmount'),
                    'totalSaleDue' => $allSaleInvoices->sum('dueAmount'),
                    'totalPurchaseInvoice' => $totalPurchaseInvoice,
                    'totalPurchaseAmount' => $allPurchaseInvoice->sum('totalAmount'),
                    'totalPurchaseDue' => $allPurchaseInvoice->sum('dueAmount')
                ];
                return $cartInfo;

            } else if ($appData->dashboardType === 'both') {

                $allSaleInvoice = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(paidAmount) as paidAmount, SUM(dueAmount) as dueAmount, SUM(profit) as profit, date')
                    ->get();

                $allPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, date')
                    ->get();

                // $totalPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                //     return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                //         ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                // })
                //     ->groupBy('date')
                //     ->orderBy('date', 'desc')
                //     ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(dueAmount) as dueAmount, SUM(paidAmount) as paidAmount, date')
                //     ->count();

                $totalPurchaseInvoice = PurchaseInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')));
                })->count();


                $totalSaleInvoice = SaleInvoice::when($request->query('startDate') && $request->query('endDate'), function ($query) use ($request) {
                    return $query->where('date', '>=', Carbon::createFromFormat('Y-m-d', $request->query('startDate')))
                        ->where('date', '<=', Carbon::createFromFormat('Y-m-d', $request->query('endDate')));
                })
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->selectRaw('COUNT(id) as countedId, SUM(totalAmount) as totalAmount, SUM(paidAmount) as paidAmount, SUM(dueAmount) as dueAmount, SUM(profit) as profit, date')
                    ->count();


                $cardInfo = [
                    'totalPurchaseInvoice' => $totalPurchaseInvoice,
                    'totalPurchaseAmount' => $allPurchaseInvoice->sum('totalAmount'),
                    'totalPurchaseDue' => $allPurchaseInvoice->sum('dueAmount'),
                ];

                return $cardInfo;
            } else {
                return ['error' => 'Invalid dashboard type'];
            }
    }
}
