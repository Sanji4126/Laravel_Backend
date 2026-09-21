<?php require "vendor/autoload.php"; $app = require_once "bootstrap/app.php"; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); 
$file = Illuminate\Http\UploadedFile::fake()->image("test.jpg");
$request = Illuminate\Http\Request::create("/api/add-product", "POST", [
    "pro_name" => "test API",
    "qty" => 1,
    "price" => 1.0,
    "description" => "desc",
    "cate_id" => 1,
    "brand_id" => 1
], [], ["image" => $file]);
$controller = $app->make(App\Http\Controllers\Products\ProductController::class);
$response = $controller->createProduct($request);
echo $response->getContent();
