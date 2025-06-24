<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UoMController;


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

Route::middleware('permission:create-uom')->post("/", [UoMController::class, 'createSingleUoM']);

Route::middleware('permission:readAll-uom')->get("/", [UoMController::class, 'getAllUoM']);

Route::middleware('permission:readAll-uom')->get("/{id}", [UoMController::class, 'getSingleUoM']);

Route::middleware('permission:update-uom')->put("/{id}", [UoMController::class, 'updateSingleUoM']);

Route::middleware('permission:delete-uom')->patch("/{id}", [UoMController::class, 'deleteSingleUoM']);
