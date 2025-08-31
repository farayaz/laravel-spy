<?php

use Farayaz\LaravelSpy\Http\Controllers\HttpLogStatsController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('spy.dashboard.prefix', 'spy'),
    'middleware' => config('spy.dashboard.middleware', ['web']),
], function () {
    Route::get('/', [HttpLogStatsController::class, 'index'])->name('spy.dashboard');
});

