<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\RoleGuideController;
use App\Http\Controllers\UserController;
use App\Livewire\Dashboard\DashboardOverview;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware(['auth', 'verified', 'account.active'])->group(function (): void {
    Route::get('/dashboard', DashboardOverview::class)->name('dashboard');
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/settings/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/settings/departments', [DepartmentController::class, 'index'])->name('departments.index');
    Route::get('/settings/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/help/roles', RoleGuideController::class)->name('help.roles');
});
