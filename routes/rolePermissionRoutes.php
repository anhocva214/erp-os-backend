<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RolePermissionController;

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

Route::middleware('permission:readSingle-rolePermission')->get("/permission", [RolePermissionController::class, 'rolePermissionByRoleId']);

// Fast API for development (no middleware)
Route::get("/permission-fast", [RolePermissionController::class, 'rolePermissionByRoleIdFast']);

Route::middleware('permission:create-rolePermission')->post("/", [RolePermissionController::class, 'createRolePermission']);
Route::middleware('permission:delete-rolePermission')->delete("/{id}", [RolePermissionController::class, 'deleteSingleRolePermission']);

