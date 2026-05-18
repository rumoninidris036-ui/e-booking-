<?php

declare(strict_types=1);

use App\Http\Controllers\Owner\BadmintonFieldController;
use App\Http\Controllers\Owner\BookingManagementController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\FieldScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->as('owner.')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/fields', [BadmintonFieldController::class, 'index'])->name('fields.index');
        Route::post('/fields', [BadmintonFieldController::class, 'store'])->name('fields.store');
        Route::get('/fields/{badmintonField}/schedule', [FieldScheduleController::class, 'show'])->name('fields.schedule');
        Route::get('/fields/{badmintonField}', [BadmintonFieldController::class, 'show'])->name('fields.show');
        Route::put('/fields/{badmintonField}', [BadmintonFieldController::class, 'update'])->name('fields.update');
        Route::delete('/fields/{badmintonField}', [BadmintonFieldController::class, 'destroy'])->name('fields.destroy');
        Route::get('/bookings', [BookingManagementController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [BookingManagementController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{booking}/status', [BookingManagementController::class, 'updateStatus'])->name('bookings.update-status');
    });
