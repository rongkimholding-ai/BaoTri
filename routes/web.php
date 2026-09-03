<?php

use App\Http\Controllers\HolidayCalendarController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MaintenanceSystemController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/maintenance-requests', [MaintenanceRequestController::class, 'index'])->name('maintenance-requests.index');
    Route::get('/maintenance-requests/{maintenanceRequest}/detail', [MaintenanceRequestController::class, 'detail'])->name('maintenance-requests.detail');
    Route::get('/maintenance-requests/export', [MaintenanceController::class, 'export'])->name('maintenance-requests.export');
    Route::post('/maintenance-requests/inline-update', [MaintenanceRequestController::class, 'inlineUpdate'])->name('maintenance-requests.inline-update');
    Route::post('/maintenance-requests/confirm', [MaintenanceRequestController::class, 'confirm'])->name('maintenance-requests.confirm');
    Route::post('/maintenance-request/acceptance', [MaintenanceRequestController::class, 'acceptance'])->name('maintenance-requests.acceptance');
    Route::post('/maintenance-requests/remind', [MaintenanceRequestController::class, 'remind'])->name('maintenance-requests.remind');
    Route::patch('/maintenance-requests/{maintenanceRequest}/status', [MaintenanceRequestController::class, 'changeStatus'])->name('maintenance-requests.change-status');
    Route::post('/maintenance-requests/update-technician-info/{id}', [MaintenanceRequestController::class, 'updateTechnicianInfo'])->name('maintenance-requests.update-technician-info');
    Route::get('/reports/technicians', [MaintenanceController::class, 'index'])->name('reports.technicians');
    Route::post('/reports/technician-update', [MaintenanceController::class, 'updateTarget'])->name('reports.technician-update');
    Route::get('/reports/technician-export', [MaintenanceController::class, 'exportTechs'])->name('reports.technician-export');
    Route::get('/maintenance/export', [MaintenanceController::class, 'exportFromTo'])->name('maintenance.export-fromto');
    Route::get('/reports/technicians-system', [MaintenanceController::class, 'reportSystem'])->name('reports.technicians_system');
    Route::get('/maintenance-system/export', [MaintenanceController::class, 'exportSystem'])->name('maintenance-system.export');
    Route::get('/reports/technician-system-export', [MaintenanceController::class, 'exportTechsSystem'])->name('reports.technician-system-export');
    Route::post('/reports/technician-system-update', [MaintenanceController::class, 'updateTargetSystem'])->name('reports.technician-system-update');
    Route::get(
        '/maintenance-requests/export-kpi',
        [MaintenanceController::class, 'exportKpi']
    )->name('maintenance-requests.export-kpi');
    Route::get(
        '/maintenance-system/export-kpi',
        [MaintenanceController::class, 'exportKpiSystem']
    )->name('maintenance-system.export-kpi');
    Route::delete(
        '/maintenance-request-images/{image}',
        [MaintenanceRequestController::class, 'destroyImage']
    )->name('maintenance-request-images.destroy');
    Route::get('/maintenance-requests/{maintenanceRequest}', [MaintenanceRequestController::class, 'show'])->name('maintenance-requests.show');
    Route::get('/system/calendar-info', [SystemController::class, 'calendarInfo'])->name('system.calendar-info');
    Route::post('/client-log', function (\Illuminate\Http\Request $request) {

        \App\Services\LogService::client(
            'CLIENT LOG',
            $request->all()
        );

        return response()->json([
            'success' => true
        ]);
    });
    Route::prefix('maintenance-system')
        ->name('maintenance-system.')
        ->group(function () {

            Route::get('/', [MaintenanceSystemController::class, 'index'])->name('index');

            Route::get('/create', [MaintenanceSystemController::class, 'create'])->name('create');

            Route::post('/', [MaintenanceSystemController::class, 'store'])->name('store');

            Route::post('/acceptance', [MaintenanceSystemController::class, 'acceptance'])->name('acceptance');

            Route::put('/update-actual-duration/{id}', [MaintenanceSystemController::class, 'updateActualDuration'])
                ->name('update-actual-duration');

            Route::put('/include-weekend/{id}', [MaintenanceSystemController::class, 'setIncludeWeekendTrue'])
                ->name('include-weekend');

            Route::post('/update-technician-info/{id}', [MaintenanceSystemController::class, 'updateTechnicianInfo'])
                ->name('update-technician-info');
              
            Route::get('/{maintenanceSystem}', [MaintenanceSystemController::class, 'show'])->name('show');

            Route::get('/{maintenanceSystem}/edit', [MaintenanceSystemController::class, 'edit'])->name('edit');

            Route::put('/{maintenanceSystem}', [MaintenanceSystemController::class, 'update'])->name('update');

            Route::delete('/{maintenanceSystem}', [MaintenanceSystemController::class, 'destroy'])->name('destroy');

            Route::post(
                '/change-status/{maintenanceSystem}/admin',
                [MaintenanceSystemController::class, 'changeStatusAdmin']
            )->name('change-status-admin');
            
            Route::get(
                '/{maintenanceSystem}/change-status/{status}',
                [MaintenanceSystemController::class, 'changeStatusForm']
            )->name('change-status.forms');

            Route::post(
                '/{maintenanceSystem}/change-status',
                [MaintenanceSystemController::class, 'changeStatus']
            )->name('change-status');

            Route::put(
                '/{maintenanceSystem}/change-status',
                [MaintenanceSystemController::class, 'changeStatus']
            )->name('change-status.update');


        });
    Route::resource('maintenance-requests', MaintenanceRequestController::class)->except(['show']);
    // Route::resource('maintenance-system', MaintenanceSystemController::class);
    Route::resource('stores', StoreController::class);
    Route::get('/tool/import-store-location', [StoreController::class, 'importLocation']);
});



Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::post(
        '/users/{user}/reset-password',
        [UserController::class, 'adminResetPassword']
    )->name('users.reset-password');
    Route::get('/users/export-excel', [UserController::class, 'exportExcel'])->name('users.export-excel');
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::resource('permissions', PermissionController::class);
    Route::get('/maintenance-requests/{maintenanceRequest}/logs', [MaintenanceRequestController::class, 'logs'])->name('maintenance-requests.logs');
});

Route::prefix('holiday-calendars')
    ->name('holiday-calendars.')
    ->group(function () {

        Route::get('/', [HolidayCalendarController::class, 'index'])
            ->name('index');

        Route::get('/create', [HolidayCalendarController::class, 'create'])
            ->name('create');

        Route::post('/', [HolidayCalendarController::class, 'store'])
            ->name('store');

        Route::get('/{holidayCalendar}/edit', [HolidayCalendarController::class, 'edit'])
            ->name('edit');

        Route::put('/{holidayCalendar}', [HolidayCalendarController::class, 'update'])
            ->name('update');

        Route::delete('/{holidayCalendar}', [HolidayCalendarController::class, 'destroy'])
            ->name('destroy');
    });

Route::get('/health-check', function () {
    return response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'time' => now(),
    ]);
});

Route::post('/select-module', function (\Illuminate\Http\Request $request) {

    $request->validate([
        'module' => 'required|in:facility,system'
    ]);

    session(['current_module' => $request->module]);

    return redirect()->to($request->redirect);
})->name('select-module');

require __DIR__ . '/auth.php';
