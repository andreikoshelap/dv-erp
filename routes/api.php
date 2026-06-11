<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// summary is cheap (DB only); ask hits the LLM and spends Anthropic credits,
// so it gets a tighter limit to blunt abuse on a public domain.
Route::get('/summary', [DashboardController::class, 'summary'])->middleware('throttle:60,1');
Route::post('/ask', [DashboardController::class, 'ask'])->middleware('throttle:10,1');
