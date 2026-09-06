<?php

use App\Http\Controllers\Api\V1\ReportScheduleController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/tokens', [TokenController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/report-schedules', [ReportScheduleController::class, 'store']);
    });
});
