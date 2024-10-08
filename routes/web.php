<?php

use App\Http\Controllers\StorageController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

require __DIR__.'/breeze.php';
require __DIR__.'/oidc.php';

Route::withoutMiddleware([VerifyCsrfToken::class])->group(function () {
    Route::get('/', [StorageController::class, 'show'])->name('home');
    Route::get('{path}', [StorageController::class, 'show'])->where('path', '.*');
    Route::put('{path}', [StorageController::class, 'create'])->where('path', '.*');
    Route::patch('{path}', [StorageController::class, 'update'])->where('path', '.*');
});
