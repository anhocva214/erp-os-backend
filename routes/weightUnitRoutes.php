<?php

use App\Http\Controllers\WeightUnitController;
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

Route::middleware('permission:create-wightUnit')->post("/", [WeightUnitController::class, 'createSingleWeightUnit']);

Route::middleware('permission:readAll-wightUnit')->get("/", [WeightUnitController::class, 'getAllWeightUnit']);

Route::middleware('permission:update-wightUnit')->put("/{id}", [WeightUnitController::class, 'updateSingleWeightUnit']);

Route::middleware('permission:delete-wightUnit')->patch("/{id}", [WeightUnitController::class, 'deleteSingleWeightUnit']);