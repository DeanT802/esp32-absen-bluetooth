<?php

use App\Http\Controllers\PresenceController;
use Illuminate\Support\Facades\Route;

Route::post('/presence', [PresenceController::class, 'store']);
Route::post('/scanner/report', [PresenceController::class, 'scannerReport']);
Route::get('/debug/latest-scan', [PresenceController::class, 'debugLatestScan']);
