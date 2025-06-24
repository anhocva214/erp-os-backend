<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EducationController;


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


Route::middleware('permission:create-education')->post("/", [EducationController::class, 'createSingleEducation']);

Route::middleware('permission:readAll-education')->get("/", [EducationController::class, 'getAllEducation']);

Route::middleware('permission:')->get("/{id}", [EducationController::class, 'getSingleEducation']);

Route::middleware('permission:update-education')->put("/{id}", [EducationController::class, 'updateSingleEducation']);

Route::middleware('permission:delete-education')->delete("/{id}", [EducationController::class, 'deleteSingleEducation']);

