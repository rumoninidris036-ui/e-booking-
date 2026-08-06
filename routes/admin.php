<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\BadmintonFieldController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'role:admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function (): void {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');
        Route::get('/users', UserManagementController::class)->name('users.index');
        Route::patch('/users/{user}/status', [UserManagementController::class, 'updateStatus'])->name('users.update-status');
        Route::get('/fields', [BadmintonFieldController::class, 'index'])->name('fields.index');
        Route::put('/fields/{badmintonField}', [BadmintonFieldController::class, 'update'])->name('fields.update');
    });
