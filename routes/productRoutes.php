<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

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

Route::middleware(['permission:create-product', 'fileUploader:9'])->post("/", [ProductController::class, 'createSingleProduct']);

Route::middleware('permission:readAll-product')->get("/", [ProductController::class, 'getAllProduct']);

// Fast API for development (minimal data, aggressive caching)
Route::get("/fast", [ProductController::class, 'getAllProductFast']);

Route::get("/public", [ProductController::class, 'getAllProductPublic']);

Route::middleware('permission:readSingle-product')->get("/{id}", [ProductController::class, 'getSingleProduct']);

Route::get("/public/{id}", [ProductController::class, 'getSingleProductPublic']);

Route::middleware(['permission:update-product', 'fileUploader:9'])->put("/{id}", [ProductController::class, 'updateSingleProduct']);

Route::middleware('permission:delete-product')->patch("/{id}", [ProductController::class, 'deleteSingleProduct']);
