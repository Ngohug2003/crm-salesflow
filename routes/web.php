<?php

use App\Http\Controllers\DepartmentController;
use App\Livewire\Dashboard\DashboardOverview;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified', 'account.active'])->group(function (): void {
    Route::get('/dashboard', DashboardOverview::class)->name('dashboard');
    Route::get('/settings/departments', [DepartmentController::class, 'index'])->name('departments.index');
});
