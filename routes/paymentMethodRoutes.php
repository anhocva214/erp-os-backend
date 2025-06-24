<?php

use App\Http\Controllers\PaymentMethodController;
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

Route::middleware('permission:create-paymentMethod', 'fileUploader:1')->post('/',[PaymentMethodController::class,'createPaymentMethod']);
Route::middleware('permission:readAll-paymentMethod')->get('/',[PaymentMethodController::class,'getAllPaymentMethods']);
Route::middleware('permission:update-paymentMethod', 'fileUploader:1')->put('/{id}',[PaymentMethodController::class,'updatePaymentMethod']);
Route::middleware('permission:delete-paymentMethod')->patch('/{id}',[PaymentMethodController::class,'deletePaymentMethod']);
