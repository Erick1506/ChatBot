<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug/info', function () {
    return response()->json([
        'php_version' => phpversion(),
        'app_key' => config('app.key'),
        'storage_is_writable' => is_writable(storage_path()),
        'log_file' => storage_path('logs/laravel.log'),
        'log_exists' => file_exists(storage_path('logs/laravel.log')),
        'vendor_autoload_exists' => file_exists(base_path('vendor/autoload.php')),
        'base_path' => base_path(),
    ]);
});

Route::get('/debug/write-log', function () {
    try {
        $path = storage_path('logs/debug_test.txt');
        file_put_contents($path, now() . " – write test\n", FILE_APPEND);

        return response()->json([
            'write_test' => 'success',
            'file' => $path,
            'content' => file_get_contents($path)
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'write_test' => 'error',
            'message' => $e->getMessage(),
        ]);
    }
});

Route::get('/debug/autoload', function () {
    try {
        require base_path('vendor/autoload.php');
        return "Autoload OK";
    } catch (\Throwable $e) {
        return "Autoload ERROR: " . $e->getMessage();
    }
});
