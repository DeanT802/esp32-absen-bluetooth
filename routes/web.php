<?php

use App\Http\Controllers\PresenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PresenceController::class, 'dashboard'])->name('dashboard');

Route::get('/employees', [PresenceController::class, 'employeesIndex'])->name('employees.index');
Route::post('/employees', [PresenceController::class, 'employeesStore'])->name('employees.store');
Route::delete('/employees/{employee}', [PresenceController::class, 'employeesDestroy'])->name('employees.destroy');

Route::get('/rooms', [PresenceController::class, 'roomsIndex'])->name('rooms.index');
Route::post('/rooms', [PresenceController::class, 'roomsStore'])->name('rooms.store');
Route::delete('/rooms/{room}', [PresenceController::class, 'roomsDestroy'])->name('rooms.destroy');

Route::get('/reports', [PresenceController::class, 'reportsIndex'])->name('reports.index');
Route::post('/reports/calculate', [PresenceController::class, 'calculateReports'])->name('reports.calculate');
Route::post('/reports/settings', [PresenceController::class, 'updateScheduleSettings'])->name('reports.settings');
Route::get('/reports/export-excel', [PresenceController::class, 'exportExcel'])->name('reports.export-excel');

Route::get('/debug', [PresenceController::class, 'debugIndex'])->name('debug.index');

