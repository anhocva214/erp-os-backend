<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalaryHistoryController;

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

Route::middleware('permission:create-salaryHistory')->post("/", [SalaryHistoryController::class, 'createSingleSalaryHistory']);

Route::middleware('permission:readAll-salaryHistory')->get("/", [SalaryHistoryController::class, 'getAllSalaryHistory']);

Route::middleware('permission:readSingle-salaryHistory')->get("/{id}", [SalaryHistoryController::class, 'getSingleSalaryHistory']);

Route::middleware('permission:update-salaryHistory')->put("/{id}", [SalaryHistoryController::class, 'updateSingleSalaryHistory']);

Route::middleware('permission:delete-salaryHistory')->delete("/{id}", [SalaryHistoryController::class, 'deleteSingleSalaryHistory']);

