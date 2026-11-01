<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeePhotoController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\Payroll\LoanController;
use App\Http\Controllers\Payroll\PayrollAdjustmentController;
use App\Http\Controllers\Payroll\PayrollAdjustmentImportController;
use App\Http\Controllers\Payroll\PayrollFinalizationController;
use App\Http\Controllers\Payroll\PayrollPeriodController;
use App\Http\Controllers\Payroll\PayrollRunController;
use App\Http\Controllers\Payroll\PayslipController;
use App\Http\Controllers\SelfService\MyAttendanceController;
use App\Http\Controllers\SelfService\MyFilingController;
use App\Http\Controllers\SelfService\MyPayslipController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\Timekeeping\FilingApprovalController;
use App\Http\Controllers\Timekeeping\FilingEndorsementController;
use App\Http\Controllers\Timekeeping\TimeLogController;
use App\Http\Controllers\Timekeeping\TimeLogImportController;
use App\Http\Controllers\Timekeeping\WorkHoursController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    // Employee kiosk (self-service).
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('attendance', MyAttendanceController::class)->name('attendance');
        Route::get('payslips', MyPayslipController::class)->name('payslips');
        Route::resource('filings', MyFilingController::class)->only(['index', 'store', 'destroy']);
    });

    Route::get('payslips/{payslip}', [PayslipController::class, 'show'])->name('payslips.show');
    Route::get('employees/{employee}/photo', [EmployeePhotoController::class, 'show'])->name('employees.photo');

    // Dept. Head endorsement of filings.
    Route::middleware('can:endorse-filings')->group(function () {
        Route::get('endorsements', [FilingEndorsementController::class, 'index'])->name('endorsements.index');
        Route::put('endorsements/{filing}', [FilingEndorsementController::class, 'update'])->name('endorsements.update');
    });

    // EMS: employee records and organization setup.
    Route::middleware('can:manage-employees')->group(function () {
        Route::resource('employees', EmployeeController::class)->except('destroy');
        Route::resource('departments', DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('branches', BranchController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('shifts', ShiftController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // TMS: time logs, filings approval and work hours summary.
    Route::middleware('can:manage-timekeeping')->prefix('timekeeping')->name('timekeeping.')->group(function () {
        Route::resource('time-logs', TimeLogController::class)->only(['index', 'store', 'destroy']);
        Route::post('time-logs/import', [TimeLogImportController::class, 'store'])->name('time-logs.import');
        Route::get('filings', [FilingApprovalController::class, 'index'])->name('filings.index');
        Route::put('filings/{filing}', [FilingApprovalController::class, 'update'])->name('filings.update');
        Route::get('work-hours', [WorkHoursController::class, 'index'])->name('work-hours.index');
        Route::get('work-hours/{employee}', [WorkHoursController::class, 'show'])->name('work-hours.show');
        Route::resource('holidays', HolidayController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // PMS: payroll processing.
    Route::middleware('can:manage-payroll')->prefix('payroll')->name('payroll.')->group(function () {
        Route::resource('periods', PayrollPeriodController::class)->only(['index', 'store', 'show', 'destroy']);
        Route::post('periods/{period}/process', [PayrollRunController::class, 'store'])->name('periods.process');
        Route::post('periods/{period}/finalize', [PayrollFinalizationController::class, 'store'])->name('periods.finalize');
        Route::post('periods/{period}/adjustments', [PayrollAdjustmentController::class, 'store'])->name('periods.adjustments.store');
        Route::post('periods/{period}/adjustments/import', [PayrollAdjustmentImportController::class, 'store'])->name('periods.adjustments.import');
        Route::delete('periods/{period}/adjustments/{adjustment}', [PayrollAdjustmentController::class, 'destroy'])
            ->scopeBindings()
            ->name('periods.adjustments.destroy');
        Route::resource('loans', LoanController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('can:manage-users');
});
