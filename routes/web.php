<?php

use Farayaz\LaravelSpy\Http\Controllers\LaravelSpyController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('spy.dashboard.prefix', 'spy'),
    'middleware' => config('spy.dashboard.middleware', ['web']),
], function () {
    Route::get('/', [LaravelSpyController::class, 'index'])->name('spy.dashboard.index');
    Route::get('/analytics', [LaravelSpyController::class, 'index'])->name('spy.dashboard.analytics');
    Route::get('/logs', [LaravelSpyController::class, 'index'])->name('spy.dashboard.logs.index');
    Route::get('/assets/apexcharts.min.js', [LaravelSpyController::class, 'chartScript'])->name('spy.dashboard.chart-script');
    Route::get('/logs/{log}', [LaravelSpyController::class, 'show'])->whereNumber('log')->name('spy.dashboard.logs.show');
});
