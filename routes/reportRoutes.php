<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['permission:readAll-productReports'])->get("/purchase", [ReportController::class, 'generatePurchaseReport']);
Route::middleware(['permission:readAll-productReports'])->get("/stock", [ReportController::class, 'generateStockReport']);
