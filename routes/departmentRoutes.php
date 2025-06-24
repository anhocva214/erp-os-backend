<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DepartmentController;

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

Route::middleware('permission:create-department')->post("/", [departmentController::class, 'createSingleDepartment']);

Route::middleware('permission:readAll-department')->get("/", [departmentController::class, 'getAllDepartment']);

Route::middleware('permission:')->get('/{id}', [departmentController::class, 'getSingleDepartment']);

Route::middleware('permission:update-department')->put("/{id}", [departmentController::class, 'updateSingleDepartment']);

Route::middleware('permission:delete-department')->patch("/{id}", [departmentController::class, 'deletedDepartment']);

