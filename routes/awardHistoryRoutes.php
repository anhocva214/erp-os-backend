<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AwardHistoryController;


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

Route::middleware("permission:create-awardHistory")->post("/", [AwardHistoryController::class, 'createSingleAwardHistory']);

Route::middleware("permission:readAll-awardHistory")->get("/", [AwardHistoryController::class, 'getAllAwardHistory']);

Route::middleware("permission:readSingle-awardHistory")->get("/{id}", [AwardHistoryController::class, 'getSingleAwardHistory']);

Route::middleware("permission:update-awardHistory")->put("/{id}", [AwardHistoryController::class, 'updateSingleAwardHistory']);

Route::middleware("permission:delete-awardHistory")->delete("/{id}", [AwardHistoryController::class, 'deleteSingleAwardHistory']);
