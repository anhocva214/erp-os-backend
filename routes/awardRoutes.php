<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AwardController;


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


Route::middleware("permission:create-award")->post("/", [AwardController::class, 'createSingleAward']);

Route::middleware("permission:readAll-award")->get("/", [AwardController::class, 'getAllAward']);

Route::middleware("permission:")->get("/{id}", [AwardController::class, 'getSingleAward']);

Route::middleware("permission:update-award")->put("/{id}", [AwardController::class, 'updateSingleAward']);

Route::middleware("permission:delete-award")->patch("/{id}", [AwardController::class, 'deleteSingleAward']);

