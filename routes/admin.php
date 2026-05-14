<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function (): void {
        Route::view('/dashboard', 'role.admin-dashboard')->name('dashboard');
    });
