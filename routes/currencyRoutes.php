<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CurrencyController;


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

Route::middleware(['permission:create-currency'])->post("/", [CurrencyController::class, 'createSingleCurrency']);

Route::middleware('permission:readAll-currency')->get("/", [CurrencyController::class, 'getAllCurrency']);

Route::middleware('permission:readSingle-currency')->get("/{id}", [CurrencyController::class, 'getSingleCurrency']);

Route::middleware(['permission:update-currency'])->put("/{id}", [CurrencyController::class, 'updateSingleCurrency']);

Route::middleware('permission:delete-currency')->patch("/{id}", [CurrencyController::class, 'deleteSingleCurrency']);
