<?php

use App\Http\Controllers\DimensionUnitController;
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


Route::middleware('permission:create-dimensionUnit')->post("/", [DimensionUnitController::class, 'createSingleDimensionUnit']);

Route::middleware('permission:readAll-dimensionUnit')->get("/", [DimensionUnitController::class, 'getAllDimensionUnit']);

Route::middleware('permission:update-dimensionUnit')->put("/{id}", [DimensionUnitController::class, 'updateSingleDimensionUnit']);

Route::middleware('permission:delete-dimensionUnit')->patch("/{id}", [DimensionUnitController::class, 'deleteSingleDimensionUnit']);