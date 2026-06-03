<?php

use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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

Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::resource('users', UserController::class);

    Route::resource('roles', RoleController::class);

    Route::resource('permissions', PermissionController::class);
});

require __DIR__.'/auth.php';
