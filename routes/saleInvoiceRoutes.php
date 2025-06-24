<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleInvoiceController;

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

Route::middleware('permission:create-saleInvoice')->post("/", [SaleInvoiceController::class, 'createSingleSaleInvoice']);

Route::middleware('permission:readAll-saleInvoice')->get("/", [SaleInvoiceController::class, 'getAllSaleInvoice']);

// Fast API for sale invoices (minimal data, aggressive caching)
Route::get("/fast", [SaleInvoiceController::class, 'getAllSaleInvoiceFast']);

Route::middleware('permission:readAll-saleInvoice')->get("/hold", [SaleInvoiceController::class, 'getAllHoldInvoice']);

Route::middleware('permission:readAll-saleInvoice')->get("/customer", [SaleInvoiceController::class, 'getAllSaleInvoiceByCustomer']);

Route::middleware('permission:readSingle-saleInvoice')->get("/customer/{id}", [SaleInvoiceController::class, 'getSingleSaleInvoiceForCustomer']);

Route::middleware('permission:readSingle-saleInvoice')->get("/{id}", [SaleInvoiceController::class, 'getSingleSaleInvoice']);

Route::middleware('permission:update-saleInvoice')->put("/{id}", [SaleInvoiceController::class, 'updateSingleSaleInvoice']);


Route::middleware('permission:update-saleInvoice')->put("/hold/{id}", [SaleInvoiceController::class, 'updateHoldInvoice']);

Route::middleware('permission:update-saleInvoice')->patch("/order", [SaleInvoiceController::class, 'updateSaleStatus']);
