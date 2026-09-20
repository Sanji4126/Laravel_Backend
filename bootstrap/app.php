<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Storage Path For Vercel Serverless
|--------------------------------------------------------------------------
*/
$customStorage = getenv('APP_STORAGE') ?: ($_ENV['APP_STORAGE'] ?? ($_SERVER['APP_STORAGE'] ?? null));
if ($customStorage) {
    $app->useStoragePath($customStorage);
} elseif (
    getenv('VERCEL') ||
    isset($_ENV['VERCEL']) ||
    isset($_SERVER['VERCEL']) ||
    getenv('NOW_REGION') ||
    getenv('AWS_LAMBDA_FUNCTION_NAME') ||
    is_dir('/tmp/storage') ||
    !is_writable($app->storagePath())
) {
    $app->useStoragePath('/tmp/storage');
}

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
