<?php

use App\Http\Controllers\ProductAttributeController;
use Illuminate\Support\Facades\Route;



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


Route::middleware("permission:create-productAttribute")->post("/", [ProductAttributeController::class, 'createProductAttribute']);

Route::middleware("permission:readAll-productAttribute")->get("/", [ProductAttributeController::class, 'getAllProductAttribute']);

Route::middleware("permission:readSingle-productAttribute")->get("/{id}", [ProductAttributeController::class, 'getSingleProductAttribute']);

Route::middleware("permission:update-productAttribute")->put("/{id}", [ProductAttributeController::class, 'updateProductAttribute']);

Route::middleware("permission:delete-productAttribute")->patch("/{id}", [ProductAttributeController::class, 'deleteProductAttribute']);

