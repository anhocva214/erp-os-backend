<?php

use App\Http\Controllers\manualPaymentController;
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

Route::middleware('permission:create-manualPayment')->post('/', [manualPaymentController::class, 'createManualPayment']);
Route::middleware('permission:readAll-manualPayment')->get('/',[manualPaymentController::class,'getAllManualPayment']);
Route::middleware('permission:readSingle-manualPayment')->get('/payment-method/{id}',[manualPaymentController::class,'totalAmountByPaymentMethodId']);
Route::middleware('permission:readSingle-manualPayment')->get('/{id}',[manualPaymentController::class,'getSingleManualPayment']);
Route::middleware('permission:update-manualPayment')->put('/verify/{id}',[manualPaymentController::class,'verifiedManualPayment']);
