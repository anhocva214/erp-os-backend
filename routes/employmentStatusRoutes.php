<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmploymentStatusController;


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

Route::middleware('permission:create-employmentStatus')->post("/", [EmploymentStatusController::class, 'createSingleEmployment']);

Route::middleware('permission:readAll-employmentStatus')->get("/", [EmploymentStatusController::class, 'getAllEmployment']);

Route::middleware('permission:readSingle-employmentStatus')->get("/{id}", [EmploymentStatusController::class, 'getSingleEmployment']);

Route::middleware('permission:update-employmentStatus')->put("/{id}", [EmploymentStatusController::class, 'updateSingleEmployment']);

Route::middleware('permission:delete-employmentStatus')->patch("/{id}", [EmploymentStatusController::class, 'deletedEmployment']);

