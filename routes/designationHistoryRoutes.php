<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DesignationHistoryController;

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

Route::middleware('permission:create-designationHistory')->post("/", [DesignationHistoryController::class, 'createSingleDesignationHistory']);

Route::middleware('permission:readAll-designationHistory')->get("/", [DesignationHistoryController::class, 'getAllDesignationHistory']);

Route::middleware('permission:readSingle-designationHistory')->get("/{id}", [DesignationHistoryController::class, 'getSingleDesignationHistory']);

Route::middleware('permission:update-designationHistory')->put("/{id}", [DesignationHistoryController::class, 'updateSingleDesignationHistory']);

Route::middleware('permission:delete-designationHistory')->delete("/{id}", [DesignationHistoryController::class, 'deleteSingleDesignationHistory']);

