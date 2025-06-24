<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnnouncementController;


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


Route::middleware("permission:create-announcement")->post("/", [AnnouncementController::class, 'createSingleAnnouncement']);

Route::middleware("permission:readAll-announcement")->get("/", [AnnouncementController::class, 'getAllAnnouncement']);

Route::middleware("permission:readSingle-announcement")->get("/{id}", [AnnouncementController::class, 'getSingleAnnouncement']);

Route::middleware("permission:update-announcement")->put("/{id}", [AnnouncementController::class, 'updateSingleAnnouncement']);

Route::middleware("permission:delete-announcement")->patch("/{id}", [AnnouncementController::class, 'deletedAnnouncement']);
