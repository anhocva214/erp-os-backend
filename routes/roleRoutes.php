<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;

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

Route::middleware('permission:create-role')->post('/', [RoleController::class, 'createSingleRole']);

Route::middleware('permission:readAll-role')->get('/', [RoleController::class, 'getAllRole']);

Route::middleware('permission:readSingle-role')->get('/{id}', [RoleController::class, 'getSingleRole']);

Route::middleware('permission:update-role')->put('/{id}', [RoleController::class, 'updateSingleRole']);

Route::middleware('permission:delete-role')->patch('/{id}', [RoleController::class, 'deleteSingleRole']);

