<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShiftController;


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

Route::middleware('permission:create-shift')->post("/", [ShiftController::class, 'createShift']);

Route::middleware('permission:readAll-shift')->get("/", [ShiftController::class, 'getAllShift']);

Route::middleware('permission:readSingle-shift')->get("/{id}", [ShiftController::class, 'getSingleShift']);

Route::middleware('permission:update-shift')->put("/{id}", [ShiftController::class, 'updateSingleShift']);

Route::middleware('permission:delete-shift')->patch("/{id}", [ShiftController::class, 'deleteSingleShift']);

