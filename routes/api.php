<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Brands\BrandController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Payment\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
});

// Allow visitors and shoppers to view catalog
Route::get('/products', [ProductController::class, 'product']);
Route::get('/categories', [CategoryController::class, 'category']);
Route::get('/brands', [BrandController::class, 'brand']);

Route::middleware(['auth:api','admin'])->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/users','user');
    });
    Route::controller(CategoryController::class)->group(function () {
        Route::post('/add-category','createCategory');
        Route::post('/update-category/{id}','updateCategory');
        Route::delete('/delete-category/{id}','deleteCategory');
    });
    Route::controller(BrandController::class)->group(function () {
        Route::post('/add-brand','createBrand');
        Route::post('/update-brand/{id}','updateBrand');
        Route::delete('/delete-brand/{id}','deleteBrand');
    });
    Route::controller(ProductController::class)->group(function () {
        Route::post('/add-product','createProduct');
        Route::post('/update-product/{id}','updateProduct');
        Route::delete('/delete-product/{id}','deleteProduct');
    });
});

Route::middleware(['auth:api'])->group(function () {
    // Cart
    Route::controller(CartController::class)->group(function () {
        Route::get('/cart', 'cart');
        Route::post('/add-cart', 'createCart');
        Route::post('/update-cart/{id}', 'updateCart');
        Route::delete('/delete-cart/{id}', 'deleteCart');
        Route::delete('/clear-cart', 'clearCart');

        // Aliases for compatibility
        Route::post('/add-to-cart', 'createCart');
        Route::post('/cart/items', 'createCart');
        Route::post('/update-cart-item/{id}', 'updateCart');
        Route::put('/cart/items/{id}', 'updateCart');
        Route::delete('/delete-cart-item/{id}', 'deleteCart');
        Route::delete('/cart/items/{id}', 'deleteCart');
        Route::delete('/cart', 'clearCart');
        Route::post('/cart/clear', 'clearCart');
    });

    // Orders
    Route::controller(OrderController::class)->group(function () {
        Route::get('/orders', 'order');
        Route::post('/add-order', 'createOrder');
        Route::post('/update-order/{id}', 'updateOrder');
        Route::delete('/delete-order/{id}', 'deleteOrder');
        Route::get('/order-detail/{id}', 'orderDetail');

        // Aliases for compatibility
        Route::post('/checkout', 'createOrder');
        Route::post('/orders', 'createOrder');
        Route::get('/orders/{id}', 'orderDetail');
        Route::put('/orders/{id}', 'updateOrder');
        Route::delete('/orders/{id}', 'deleteOrder');
    });

    // Payments
    Route::controller(PaymentController::class)->group(function () {
        Route::get('/payments', 'payment');
        Route::post('/add-payment', 'createPayment');
        Route::get('/payment-detail/{id}', 'paymentDetail');

        // Aliases for compatibility
        Route::post('/payments', 'createPayment');
        Route::get('/payments/{id}', 'paymentDetail');
    });
});