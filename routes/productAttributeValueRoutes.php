<?php


use App\Http\Controllers\ProductAttributeValueController;
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


Route::middleware("permission:create-productAttributeValue")->post("/", [ProductAttributeValueController::class, 'createSingleProductAttributeValue']);

Route::middleware("permission:readAll-productAttributeValue")->get("/", [ProductAttributeValueController::class, 'getAllProductAttributeValue']);

Route::middleware("permission:readSingle-productAttributeValue")->get("/{id}", [ProductAttributeValueController::class, 'getSingleProductAttributeValue']);

Route::middleware("permission:update-productAttributeValue")->put("/{id}", [ProductAttributeValueController::class, 'updateSingleProductAttributeValue']);

Route::middleware("permission:delete-productAttributeValue")->patch("/{id}", [ProductAttributeValueController::class, 'deleteSingleProductAttributeValue']);

