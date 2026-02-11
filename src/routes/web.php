<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Kubernetes liveness probe - process is alive
Route::get('/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Kubernetes readiness probe - DB is reachable
Route::get('/ready', function () {
    $checks = [];

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = $e->getMessage();
        return response()->json(['status' => 'not ready', 'checks' => $checks], 503);
    }

    return response()->json(['status' => 'ready', 'checks' => $checks], 200);
});

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});
