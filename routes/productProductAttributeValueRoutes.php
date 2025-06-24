<?php


use App\Http\Controllers\ProductProductAttributeValueController;
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

Route::middleware("permission:create-productProductAttributeValue")->post("/", [ProductProductAttributeValueController::class, 'createProductProductAttributeValue']);

Route::middleware("permission:readAll-productProductAttributeValue")->get("/", [ProductProductAttributeValueController::class, 'getAllProductProductAttributeValue']);

Route::middleware("permission:readSingle-productProductAttributeValue")->get("/{id}", [ProductProductAttributeValueController::class, 'getSingleProductProductAttributeValue']);

Route::middleware("permission:update-productProductAttributeValue")->put("/{id}", [ProductProductAttributeValueController::class, 'updateProductProductAttributeValue']);

Route::middleware("permission:delete-productProductAttributeValue")->patch("/{id}", [ProductProductAttributeValueController::class, 'deleteProductProductAttributeValue']);

