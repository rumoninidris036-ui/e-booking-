<?php

declare(strict_types=1);

use App\Http\Controllers\Owner\BadmintonFieldController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:owner'])
    ->prefix('owner')
    ->as('owner.')
    ->group(function (): void {
        Route::view('/dashboard', 'role.owner-dashboard')->name('dashboard');
        Route::get('/fields', [BadmintonFieldController::class, 'index'])->name('fields.index');
        Route::post('/fields', [BadmintonFieldController::class, 'store'])->name('fields.store');
        Route::get('/fields/{badmintonField}', [BadmintonFieldController::class, 'show'])->name('fields.show');
        Route::put('/fields/{badmintonField}', [BadmintonFieldController::class, 'update'])->name('fields.update');
        Route::delete('/fields/{badmintonField}', [BadmintonFieldController::class, 'destroy'])->name('fields.destroy');
    });
