<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\googleLoginController;






Route::post('/login', [googleLoginController::class, 'handleGoogleCallback']);
