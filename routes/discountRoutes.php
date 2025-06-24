<?php

use App\Http\Controllers\DiscountController;
use Illuminate\Support\Facades\Route;


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

Route::middleware('permission:create-discount')->post("/", [DiscountController::class, 'createSingleDiscount']);

Route::middleware('permission:readAll-discount')->get("/", [DiscountController::class, 'getAllDiscount']);

Route::middleware('permission:readAll-discount')->get("/{id}", [DiscountController::class, 'getSingleDiscount']);

Route::middleware('permission:update-discount')->put("/{id}", [DiscountController::class, 'updateSingleDiscount']);

Route::middleware('permission:delete-discount')->patch("/{id}", [DiscountController::class, 'deleteSingleDiscount']);


