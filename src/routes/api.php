<?php

use App\Http\Controllers\Api\StatsController;
use App\Http\Middleware\InternalApiKey;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([InternalApiKey::class])->group(function () {
    Route::get('/posts/{postUuid}/stats', [StatsController::class, 'postStats']);
    Route::get('/authors/{userId}/stats', [StatsController::class, 'authorStats']);
    Route::get('/trending', [StatsController::class, 'trending']);
});
