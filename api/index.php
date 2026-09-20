<?php

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);

// Prepare writable storage directory in /tmp for Vercel serverless environment
$storagePath = '/tmp/storage';
$dirs = [
    $storagePath . '/framework/views',
    $storagePath . '/framework/cache',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/logs',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

if (isset($_GET['__debug_vercel'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null,
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null,
        'PATH_INFO' => $_SERVER['PATH_INFO'] ?? null,
        'PHP_SELF' => $_SERVER['PHP_SELF'] ?? null,
        'APP_KEY_SET' => !empty($_ENV['APP_KEY']) || !empty(getenv('APP_KEY')),
        'DB_HOST' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'not_set',
        'DB_CONNECTION' => $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION') ?: 'not_set',
    ], JSON_PRETTY_PRINT);
    exit;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';

// Forward Vercel request to Laravel public index.php
require __DIR__ . '/../public/index.php';
