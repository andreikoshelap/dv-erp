<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/summary', [DashboardController::class, 'summary']);
Route::post('/ask', [DashboardController::class, 'ask']);
