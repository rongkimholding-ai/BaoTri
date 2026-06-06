<?php

use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/login');
});

Route::get('/dashboard', function () {
    return redirect()->route('maintenance-requests.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/maintenance-requests',[MaintenanceRequestController::class, 'index'])->name('maintenance-requests.index');
    Route::get('/maintenance-requests/export',[MaintenanceController::class, 'export'])->name('maintenance-requests.export');
    Route::post('/maintenance-requests/inline-update',[MaintenanceRequestController::class, 'inlineUpdate'])->name('maintenance-requests.inline-update');
    Route::resource('maintenance-requests', MaintenanceRequestController::class);

    Route::get('/reports/technicians',[MaintenanceController::class, 'index'])->name('reports.technicians');
    Route::post(
        '/reports/technician-update',
        [MaintenanceController::class, 'updateTarget']
    )->name('reports.technician-update');
    Route::get(
        '/reports/technician-export',
        [MaintenanceController::class, 'exportTechs']
    )->name('reports.technician-export');

    Route::post(
        '/maintenance-requests/confirm',
        [MaintenanceRequestController::class, 'confirm']
    )
    ->name('maintenance-requests.confirm');
});

Route::post(
    '/maintenance-requests/remind',
    [MaintenanceRequestController::class, 'remind']
)->name('maintenance-requests.remind');

Route::patch(
    '/maintenance-requests/{maintenanceRequest}/status',
    [MaintenanceRequestController::class, 'changeStatus']
)->name('maintenance-requests.change-status');



Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::resource('users', UserController::class);

    Route::resource('roles', RoleController::class);

    Route::resource('permissions', PermissionController::class);

    Route::get(
        '/maintenance-requests/{maintenanceRequest}/logs',
        [MaintenanceRequestController::class, 'logs']
    )->name('maintenance-requests.logs');
});

require __DIR__.'/auth.php';
