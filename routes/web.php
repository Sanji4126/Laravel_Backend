<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'php' => PHP_VERSION,
        'app_key_exists' => !empty(config('app.key')),
        'db_host' => config('database.connections.pgsql.host'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
    ]);
});

Route::get('/health/db', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        return response()->json(['database' => 'connected']);
    } catch (\Throwable $e) {
        return response()->json([
            'database' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/', function () {
    try {
        return view('welcome');
    } catch (\Throwable $e) {
        return response()->json([
            'error' => 'view_rendering_error',
            'message' => $e->getMessage()
        ], 500);
    }
});

