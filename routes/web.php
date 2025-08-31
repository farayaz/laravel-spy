<?php

use Farayaz\LaravelSpy\Http\Controllers\LaravelSpyController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('spy.dashboard.prefix', 'spy'),
    'middleware' => config('spy.dashboard.middleware', ['web']),
], function () {
    Route::get('/', [LaravelSpyController::class, 'index'])->name('spy.dashboard');
});

