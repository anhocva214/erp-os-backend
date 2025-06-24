<?php

use App\Http\Controllers\ManufacturerController;
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

Route::middleware('permission:create-manufacturer')->post("/", [ManufacturerController::class, 'createSingleManufacturer']);

Route::middleware('permission:readAll-manufacturer')->get("/", [ManufacturerController::class, 'getAllManufacturer']);

Route::middleware('permission:readAll-manufacturer')->get("/{id}", [ManufacturerController::class, 'getSingleManufacturer']);

Route::middleware('permission:update-manufacturer')->put("/{id}", [ManufacturerController::class, 'updateSingleManufacturer']);

Route::middleware('permission:delete-manufacturer')->patch("/{id}", [ManufacturerController::class, 'deleteSingleManufacturer']);