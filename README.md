# Ecommerce Backend API - Developer Guide

Backend API for an ecommerce application built with Laravel 10, MySQL, and Laravel Sanctum.

---

## 1. Project Status & Database Setup

All database migrations have been executed and the tables are ready in MySQL:

| Table | Primary Key | Foreign Keys / Key Columns |
| --- | --- | --- |
| `users` | `user_id` | `user_name`, `email` (unique), `password`, `role` (admin/user) |
| `categories` | `cate_id` | `cate_name`, `user_id` (FK to `users`) |
| `brands` | `brand_id` | `brand_name`, `user_id` (FK to `users`) |
| `products` | `product_id` | `product_name`, `cate_id` (FK), `brand_id` (FK), `stock`, `price`, `image`, `user_id` (FK) |
| `carts` | `cart_id` | `user_id` (FK to `users`) |
| `cart_items` | `cart_item_id` | `cart_id` (FK to `carts`), `product_id` (FK to `products`), `quantity` |
| `orders` | `order_id` | `user_id` (FK to `users`), `order_date`, `status`, `subtotal`, `shipping_fee`, `discount`, `total` |
| `order_items` | `order_item_id` | `order_id` (FK to `orders`), `product_id` (nullable FK), `product_name`, `quantity`, `unit_price`, `subtotal` |
| `payments` | `payment_id` | `order_id` (FK to `orders`), `payment_method`, `transaction_id`, `amount`, `status`, `paid_at` |

### Eloquent Models (`app/Models/`)
The models are defined with relationships and custom primary keys matching the database schema:
- `User.php` (`$primaryKey = 'user_id'`)
- `Category.php` (`$primaryKey = 'cate_id'`)
- `Brand.php` (`$primaryKey = 'brand_id'`)
- `Product.php` (`$primaryKey = 'product_id'`)
- `Cart.php` (`$primaryKey = 'cart_id'`)
- `CartItem.php` (`$primaryKey = 'cart_item_id'`)
- `Order.php` (`$primaryKey = 'order_id'`)
- `OrderItem.php` (`$primaryKey = 'order_item_id'`)
- `Payment.php` (`$primaryKey = 'payment_id'`)

---

## 2. Controller Files

Blank controllers have been generated via `php artisan make:controller`:

```
app/Http/Controllers/
├── Brand/
│   └── BrandController.php
├── Cart/
│   └── CartController.php
├── CartItem/
│   └── CartItemController.php
├── Categories/
│   └── CategoryController.php
├── Order/
│   └── OrderController.php
├── Payment/
│   └── PaymentController.php
├── Products/
│   └── ProductController.php
└── Users/
    ├── AuthController.php
    └── UserController.php
```

---

## 3. How to Implement the Logic (Step-by-Step)

### Step 3.1: Define Routes in `routes/api.php`

Open `routes/api.php` and map your controller actions:

```php
use App\Http\Controllers\Users\AuthController;
use App\Http\Controllers\Users\UserController;
use App\Http\Controllers\Categories\CategoryController;
use App\Http\Controllers\Brand\BrandController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\Cart\CartController;
use App\Http\Controllers\CartItem\CartItemController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Payment\PaymentController;
use Illuminate\Support\Facades\Route;

// --- Public Routes ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{id}', [BrandController::class, 'show']);

// --- Authenticated Routes (Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'profile']);

    // Admin / User Management
    Route::apiResource('users', UserController::class);

    // Categories & Brands Management
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    Route::post('/brands', [BrandController::class, 'store']);
    Route::put('/brands/{id}', [BrandController::class, 'update']);
    Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

    // Products Management
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

    // Cart & Cart Items
    Route::get('/cart', [CartController::class, 'show']);
    Route::delete('/cart', [CartController::class, 'clear']);
    Route::post('/cart/items', [CartItemController::class, 'store']);
    Route::put('/cart/items/{id}', [CartItemController::class, 'update']);
    Route::delete('/cart/items/{id}', [CartItemController::class, 'destroy']);

    // Orders & Checkout
    Route::apiResource('orders', OrderController::class);

    // Payments
    Route::apiResource('payments', PaymentController::class);
});
```

---

### Step 3.2: Authentication Logic in `AuthController.php`

Inside `app/Http/Controllers/Users/AuthController.php`:

1. **`register(Request $request)`**:
   - Validate incoming data: `user_name`, `email`, `password` (min 8 chars, confirmed), and optional `role` (`in:admin,user`).
   - Create user using `Hash::make($request->password)`.
   - Generate Sanctum token: `$token = $user->createToken('auth_token')->plainTextToken;`.
   - Return JSON with user data and the token with HTTP status `201`.

2. **`login(Request $request)`**:
   - Validate `email` and `password`.
   - Query user by email: `User::where('email', $request->email)->first()`.
   - Check password using `Hash::check($request->password, $user->password)`.
   - If invalid, return an error message (HTTP 401 or 422).
   - If valid, issue token: `$token = $user->createToken('auth_token')->plainTextToken;`.

3. **`logout(Request $request)`**:
   - Revoke current token: `$request->user()->currentAccessToken()->delete();`.
   - Return success message.

4. **`profile(Request $request)`**:
   - Return `$request->user()`.

---

### Step 3.3: CRUD Logic for Categories, Brands & Products

#### `CategoryController.php` & `BrandController.php`:
- **`index()`**: Return all items with product counts, e.g., `Category::withCount('products')->get()`.
- **`store(Request $request)`**: Validate `cate_name` / `brand_name`, set `user_id => $request->user()->user_id`, and save.
- **`show(string $id)`**: Return record with products: `Category::with('products')->findOrFail($id)`.
- **`update(Request $request, string $id)`**: Validate name and call `$item->update($request->only(...))`.
- **`destroy(string $id)`**: Call `$item->delete()`.

#### `ProductController.php`:
- **`index(Request $request)`**:
  - Support query filters like `cate_id`, `brand_id`, and search keywords (`where('product_name', 'like', "%{$search}%")`).
  - Eager-load relations: `Product::with(['category', 'brand'])->paginate(15)`.
- **`store(Request $request)`**:
  - Validate: `product_name`, `cate_id` (`exists:categories,cate_id`), `brand_id` (`exists:brands,brand_id`), `stock`, `price`, `image`.
  - Assign `user_id = $request->user()->user_id`.
  - Save and return created product.
- **`update(Request $request, string $id)`**: Validate and update fields.
- **`destroy(string $id)`**: Find product and delete.

---

### Step 3.4: Cart & Cart Items Logic

#### `CartController.php`:
- **`show(Request $request)`**:
  - Find or create user's cart:
    ```php
    $cart = Cart::firstOrCreate(['user_id' => $request->user()->user_id]);
    $cart->load('cartItems.product');
    return response()->json($cart);
    ```
- **`clear(Request $request)`**:
  - Delete all items belonging to user's cart: `$cart->cartItems()->delete()`.

#### `CartItemController.php`:
- **`store(Request $request)`**:
  - Validate `product_id` and `quantity`.
  - Check product stock: ensure `stock >= quantity`.
  - Check if product already exists in user's cart:
    - If yes: increment existing quantity: `$item->increment('quantity', $quantity)`.
    - If no: create new `CartItem` linked to `$cart->cart_id`.
- **`update(Request $request, string $id)`**:
  - Update quantity of specific cart item.
- **`destroy(string $id)`**:
  - Find `CartItem` and delete it.

---

### Step 3.5: Order & Checkout Logic (`OrderController.php`)

- **`store(Request $request)`**:
  1. Retrieve user's cart and items with `product`.
  2. If cart is empty, return error (HTTP 422).
  3. Wrap in a database transaction (`DB::transaction(function () { ... })`):
     - Calculate `subtotal` by iterating over cart items (`$item->product->price * $item->quantity`).
     - Deduct stock from each product: `$item->product->decrement('stock', $item->quantity)`.
     - Calculate `total = ($subtotal + $shipping_fee) - $discount`.
     - Create `Order` record with status `pending`.
     - Clear items from cart: `$cart->cartItems()->delete()`.
  4. Return created order.
- **`index(Request $request)`**:
  - If admin: can view all orders.
  - If regular user: return orders where `user_id = $user->user_id`.
- **`show(string $id)`**: Return order with its payments.
- **`update(Request $request, string $id)`**: Update status (`pending`, `processing`, `completed`, `cancelled`).

---

### Step 3.6: Payment Logic (`PaymentController.php`)

- **`store(Request $request)`**:
  - Validate: `order_id` (`exists:orders,order_id`), `payment_method`, `amount`.
  - Create payment record:
    ```php
    Payment::create([
        'order_id' => $order->order_id,
        'payment_method' => $request->payment_method,
        'transaction_id' => 'TXN-' . strtoupper(Str::random(10)),
        'amount' => $request->amount,
        'status' => 'completed',
        'paid_at' => now(),
    ]);
    ```
  - Check if total paid covers order total; if yes, update order status to `processing`.

---

## 4. Useful Artisan Commands

- Start local server:
  ```bash
  php artisan serve
  ```
- Check migration status:
  ```bash
  php artisan migrate:status
  ```
- Rollback migrations:
  ```bash
  php artisan migrate:rollback
  ```
- List all registered routes:
  ```bash
  php artisan route:list
  ```
- Run tests:
  ```bash
  php artisan test
  ```