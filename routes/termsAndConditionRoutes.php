<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TermsAndConditionController;

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

Route::middleware('permission:create-termsAndCondition')->post("/", [TermsAndConditionController::class, 'createSingletermsAndCondition']);

Route::middleware('permission:readAll-termsAndCondition')->get("/", [TermsAndConditionController::class, 'getAlltermsAndCondition']);

Route::middleware('permission:readSingle-termsAndCondition')->get("/{id}", [TermsAndConditionController::class, 'getSingletermsAndCondition']);

Route::middleware('permission:update-termsAndCondition')->put("/{id}", [TermsAndConditionController::class, 'updateSingletermsAndCondition']);

Route::middleware('permission:delete-termsAndCondition')->patch("/{id}", [TermsAndConditionController::class, 'deleteSingletermsAndCondition']);
