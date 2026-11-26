<?php

use Illuminate\Support\Facades\Route;
use WikaGroup\SessionService\Http\Controllers\SessionServiceController;
use WikaGroup\SessionService\Http\Middleware\EnsureSessionServiceAuthenticated;

Route::middleware('web')->prefix('sessionservice')->group(function (): void {
    Route::get(config('sessionservice.routes.login', '/login'), [SessionServiceController::class, 'login'])
        ->withoutMiddleware(EnsureSessionServiceAuthenticated::class)
        ->name('sessionservice.login');

    Route::get(config('sessionservice.routes.callback'), [SessionServiceController::class, 'callback'])
        ->withoutMiddleware(EnsureSessionServiceAuthenticated::class)
        ->name('sessionservice.callback');

    Route::post(config('sessionservice.routes.logout'), [SessionServiceController::class, 'logout'])
        ->name('sessionservice.logout');
});
