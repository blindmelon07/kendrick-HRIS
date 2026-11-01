<?php

use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\EndorsementController;
use App\Http\Controllers\Api\V1\FilingController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProfilePhotoController;
use App\Http\Middleware\EnsureUserHasEmployeeRecord;
use Illuminate\Support\Facades\Route;

/*
| Mobile app (employee kiosk + Dept. Head endorsements).
*/
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:login')->name('auth.token.store');

    Route::middleware(['auth:sanctum', EnsureUserHasEmployeeRecord::class])->group(function () {
        Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        Route::get('me', [ProfileController::class, 'show'])->name('me');
        Route::get('me/photo', [ProfilePhotoController::class, 'show'])->name('me.photo');
        Route::get('home', HomeController::class)->name('home');
        Route::get('attendance', AttendanceController::class)->name('attendance');
        Route::apiResource('filings', FilingController::class)->only(['index', 'store', 'destroy']);
        Route::apiResource('payslips', PayslipController::class)->only(['index', 'show']);

        Route::middleware('can:endorse-filings')->group(function () {
            Route::get('endorsements', [EndorsementController::class, 'index'])->name('endorsements.index');
            Route::put('endorsements/{filing}', [EndorsementController::class, 'update'])->name('endorsements.update');
        });
    });
});
