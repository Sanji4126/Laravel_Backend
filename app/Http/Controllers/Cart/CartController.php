<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function cart()
    {
        try {
            $userId = auth('api')->id();
            $cart = Cart::firstOrCreate(['user_id' => $userId]);
            $cart->load('cartItems.product');

            $subtotal = 0;
            foreach ($cart->cartItems as $item) {
                if ($item->product) {
                    $subtotal += ((float) $item->product->price * $item->quantity);
                }
            }
            $cart->subtotal = number_format($subtotal, 2, '.', '');
            $cart->total_items = $cart->cartItems->sum('quantity');

            return response()->json([
                'message' => 'get data successfully',
                'cart' => $cart,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'get cart failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createCart(Request $request)
    {
        try {
            $quantity = $request->input('quantity', $request->input('qty', 1));
            $request->merge(['quantity' => $quantity]);

            $data = $request->validate([
                'product_id' => 'required|exists:products,product_id',
                'quantity' => 'required|integer|min:1',
            ]);

            $userId = auth('api')->id();
            $product = Product::findOrFail($data['product_id']);

            if ($product->stock < $data['quantity']) {
                return response()->json([
                    'message' => "Insufficient stock. Only {$product->stock} available.",
                ], 422);
            }

            $cart = Cart::firstOrCreate(['user_id' => $userId]);

            $item = CartItem::where('cart_id', $cart->cart_id)
                ->where('product_id', $product->product_id)
                ->first();

            if ($item) {
                $newQuantity = $item->quantity + (int) $data['quantity'];
                if ($product->stock < $newQuantity) {
                    return response()->json([
                        'message' => "Insufficient stock. You already have {$item->quantity} in cart and only {$product->stock} are available.",
                    ], 422);
                }
                $item->update(['quantity' => $newQuantity]);
            } else {
                $item = CartItem::create([
                    'cart_id' => $cart->cart_id,
                    'product_id' => $product->product_id,
                    'quantity' => (int) $data['quantity'],
                ]);
            }

            $cart->load('cartItems.product');

            return response()->json([
                'message' => 'Cart created successfully',
                'cart' => $cart,
                'item' => $item->load('product'),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cart created failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateCart(Request $request, $id)
    {
        try {
            $quantity = $request->input('quantity', $request->input('qty'));
            $request->merge(['quantity' => $quantity]);

            $data = $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $userId = auth('api')->id();
            $cart = Cart::where('user_id', $userId)->first();

            if (!$cart) {
                return response()->json(['message' => 'Cart not found'], 404);
            }

            $item = CartItem::where('cart_id', $cart->cart_id)
                ->where(function ($query) use ($id) {
                    $query->where('cart_item_id', $id)
                          ->orWhere('product_id', $id);
                })
                ->with('product')
                ->first();

            if (!$item) {
                return response()->json(['message' => 'Cart item not found'], 404);
            }

            if ($item->product && $item->product->stock < $data['quantity']) {
                return response()->json([
                    'message' => "Insufficient stock. Only {$item->product->stock} available.",
                ], 422);
            }

            $item->update(['quantity' => (int) $data['quantity']]);
            $cart->load('cartItems.product');

            return response()->json([
                'message' => 'Cart updated successfully',
                'cart' => $cart,
                'item' => $item->fresh('product'),
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cart updated failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteCart($id)
    {
        try {
            $userId = auth('api')->id();
            $cart = Cart::where('user_id', $userId)->first();

            if (!$cart) {
                return response()->json(['message' => 'Cart not found'], 404);
            }

            $item = CartItem::where('cart_id', $cart->cart_id)
                ->where(function ($query) use ($id) {
                    $query->where('cart_item_id', $id)
                          ->orWhere('product_id', $id);
                })
                ->first();

            if (!$item) {
                return response()->json(['message' => 'Cart item not found'], 404);
            }

            $item->delete();

            return response()->json([
                'message' => 'Cart deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cart deleted failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function clearCart()
    {
        try {
            $userId = auth('api')->id();
            $cart = Cart::where('user_id', $userId)->first();

            if ($cart) {
                $cart->cartItems()->delete();
            }

            return response()->json([
                'message' => 'Cart cleared successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cart cleared failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Backward compatibility aliases
    public function show(Request $request) { return $this->cart(); }
    public function index(Request $request) { return $this->cart(); }
    public function store(Request $request) { return $this->createCart($request); }
    public function update(Request $request, $id) { return $this->updateCart($request, $id); }
    public function destroy($id) { return $this->deleteCart($id); }
    public function clear(Request $request) { return $this->clearCart(); }
}
