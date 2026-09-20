<?php

// Enable error reporting for logs
error_reporting(E_ALL & ~E_DEPRECATED);

// 1. Prepare writable storage directory in /tmp for Vercel serverless environment
$storagePath = '/tmp/storage';
$dirs = [
    $storagePath . '/framework/views',
    $storagePath . '/framework/cache',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/bootstrap/cache',
    $storagePath . '/logs',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. Copy pre-compiled bootstrap caches if they exist
$srcBootstrapCache = __DIR__ . '/../bootstrap/cache';
if (is_dir($srcBootstrapCache)) {
    $files = @scandir($srcBootstrapCache) ?: [];
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && $file !== '.gitignore') {
            $dest = $storagePath . '/bootstrap/cache/' . $file;
            if (!file_exists($dest)) {
                @copy($srcBootstrapCache . '/' . $file, $dest);
            }
        }
    }
}

// 3. Set environment variables for storage and caches in /tmp
$serverlessEnv = [
    'APP_STORAGE' => $storagePath,
    'VIEW_COMPILED_PATH' => $storagePath . '/framework/views',
    'APP_CONFIG_CACHE' => $storagePath . '/bootstrap/cache/config.php',
    'APP_EVENTS_CACHE' => $storagePath . '/bootstrap/cache/events.php',
    'APP_PACKAGES_CACHE' => $storagePath . '/bootstrap/cache/packages.php',
    'APP_ROUTES_CACHE' => $storagePath . '/bootstrap/cache/routes.php',
    'APP_SERVICES_CACHE' => $storagePath . '/bootstrap/cache/services.php',
];

foreach ($serverlessEnv as $key => $val) {
    putenv("{$key}={$val}");
    $_ENV[$key] = $val;
    $_SERVER[$key] = $val;
}

// 4. Default driver fallbacks for serverless if not set in Vercel dashboard
$driverDefaults = [
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'cookie',
    'LOG_CHANNEL' => 'stderr',
    'AWS_DEFAULT_REGION' => 'ap-south-1',
];

foreach ($driverDefaults as $key => $val) {
    if (!getenv($key) && empty($_ENV[$key]) && empty($_SERVER[$key])) {
        putenv("{$key}={$val}");
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

// 5. Diagnostic debug endpoint: ?__debug_vercel=1
if (isset($_GET['__debug_vercel'])) {
    header('Content-Type: application/json');
    $appKey = getenv('APP_KEY') ?: ($_ENV['APP_KEY'] ?? ($_SERVER['APP_KEY'] ?? null));
    echo json_encode([
        'status' => 'vercel_entry_point_ok',
        'php_version' => PHP_VERSION,
        'app_key_configured' => !empty($appKey),
        'app_key_preview' => !empty($appKey) ? substr($appKey, 0, 10) . '...' : 'MISSING (Add APP_KEY in Vercel Settings)',
        'app_env' => getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'not_set'),
        'db_connection' => getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'not_set'),
        'db_host' => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'not_set'),
        'storage_path' => $storagePath,
        'storage_writable' => is_writable($storagePath),
        'tmp_views_writable' => is_writable($storagePath . '/framework/views'),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // 6. Forward request to Laravel public index.php
    require __DIR__ . '/../public/index.php';
} catch (\Throwable $e) {
    // Log exception to stderr for Vercel Function Logs
    error_log('[Vercel Serverless Exception] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

    http_response_code(500);
    header('Content-Type: application/json');

    $isDebug = (getenv('APP_DEBUG') === 'true' || 
                ($_ENV['APP_DEBUG'] ?? null) === 'true' || 
                ($_SERVER['APP_DEBUG'] ?? null) === 'true' || 
                isset($_GET['debug']));

    if ($isDebug) {
        echo json_encode([
            'status' => 500,
            'error' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString()),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode([
            'status' => 500,
            'error' => 'Internal Server Error',
            'message' => $e->getMessage() ?: 'An unexpected error occurred.',
            'hint' => 'Check Vercel Project Settings > Environment Variables for missing APP_KEY or database connection credentials. Add ?debug=1 or set APP_DEBUG=true to view full trace.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    exit;
}
