<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function order()
    {
        try {
            $user = auth('api')->user();

            if ($user->role === 'admin') {
                $orders = Order::with(['items.product', 'payments', 'user'])->latest()->get();
            } else {
                $orders = Order::with(['items.product', 'payments'])->where('user_id', $user->user_id)->latest()->get();
            }

            return response()->json([
                'message' => 'get data successfully',
                'order' => $orders,
                'orders' => $orders,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'get orders failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function orderDetail($id)
    {
        try {
            $user = auth('api')->user();
            $order = Order::with(['items.product', 'payments', 'user'])->findOrFail($id);

            if ($user->role !== 'admin' && $order->user_id !== $user->user_id) {
                return response()->json(['message' => 'Unauthorized access to this order'], 403);
            }

            return response()->json([
                'message' => 'get data successfully',
                'order' => $order,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Order not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function createOrder(Request $request)
    {
        try {
            $request->validate([
                'shipping_fee' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'payment_method' => 'nullable|string|max:50',
                'items' => 'nullable|array',
                'items.*.product_id' => 'required_with:items|exists:products,product_id',
                'items.*.quantity' => 'nullable|integer|min:1',
                'items.*.qty' => 'nullable|integer|min:1',
            ]);

            $user = auth('api')->user();
            $userId = auth('api')->id();
            $hasDirectItems = $request->has('items') && is_array($request->input('items')) && count($request->input('items')) > 0;

            $itemsToProcess = [];
            $cart = null;

            if ($hasDirectItems) {
                foreach ($request->input('items') as $rawItem) {
                    $productId = $rawItem['product_id'];
                    $quantity = (int) ($rawItem['quantity'] ?? $rawItem['qty'] ?? 1);
                    $product = Product::findOrFail($productId);

                    if ($product->stock < $quantity) {
                        return response()->json([
                            'message' => "Insufficient stock for '{$product->product_name}'. In stock: {$product->stock}, requested: {$quantity}",
                        ], 422);
                    }

                    $itemsToProcess[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                    ];
                }
            } else {
                $cart = Cart::where('user_id', $userId)->with('cartItems.product')->first();

                if (!$cart || $cart->cartItems->isEmpty()) {
                    return response()->json([
                        'message' => 'Your cart is empty',
                    ], 422);
                }

                foreach ($cart->cartItems as $cartItem) {
                    if (!$cartItem->product) {
                        return response()->json([
                            'message' => "Product not found for item #{$cartItem->cart_item_id}",
                        ], 422);
                    }

                    if ($cartItem->product->stock < $cartItem->quantity) {
                        return response()->json([
                            'message' => "Insufficient stock for '{$cartItem->product->product_name}'. In stock: {$cartItem->product->stock}, in cart: {$cartItem->quantity}",
                        ], 422);
                    }

                    $itemsToProcess[] = [
                        'product' => $cartItem->product,
                        'quantity' => $cartItem->quantity,
                    ];
                }
            }

            $order = DB::transaction(function () use ($userId, $itemsToProcess, $cart, $request) {
                $subtotal = 0;
                foreach ($itemsToProcess as $item) {
                    $subtotal += ($item['product']->price * $item['quantity']);
                }

                $shippingFee = (float) $request->input('shipping_fee', 0);
                $discount = (float) $request->input('discount', 0);
                $total = max(0, ($subtotal + $shippingFee) - $discount);

                $order = Order::create([
                    'user_id' => $userId,
                    'order_date' => now(),
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'discount' => $discount,
                    'total' => $total,
                ]);

                foreach ($itemsToProcess as $item) {
                    OrderItem::create([
                        'order_id' => $order->order_id,
                        'product_id' => $item['product']->product_id,
                        'product_name' => $item['product']->product_name,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['product']->price,
                        'subtotal' => $item['product']->price * $item['quantity'],
                    ]);

                    $item['product']->decrement('stock', $item['quantity']);
                }

                if ($request->filled('payment_method')) {
                    Payment::create([
                        'order_id' => $order->order_id,
                        'payment_method' => $request->input('payment_method'),
                        'transaction_id' => 'TXN-' . strtoupper(Str::random(12)),
                        'amount' => $total,
                        'status' => 'pending',
                        'paid_at' => null,
                    ]);
                }

                if ($cart) {
                    $cart->cartItems()->delete();
                }

                return $order;
            });

            $order->load(['items', 'payments']);

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Order created failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateOrder(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|string|in:pending,processing,completed,cancelled',
            ]);

            $user = auth('api')->user();
            $order = Order::with('items.product')->findOrFail($id);

            if ($user->role !== 'admin' && $order->user_id !== $user->user_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Only admin can set to processing/completed, regular user can only cancel their pending order
            if ($user->role !== 'admin' && $request->status !== 'cancelled') {
                return response()->json(['message' => 'Only admins can update order status to ' . $request->status], 403);
            }

            // If cancelling order, restore stock
            if ($request->status === 'cancelled' && $order->status !== 'cancelled') {
                foreach ($order->items as $orderItem) {
                    if ($orderItem->product) {
                        $orderItem->product->increment('stock', $orderItem->quantity);
                    }
                }
            }

            $order->update(['status' => $request->status]);

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $order,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Order updated failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteOrder($id)
    {
        try {
            $user = auth('api')->user();
            if ($user->role !== 'admin') {
                return response()->json(['message' => 'Only admins can delete orders'], 403);
            }

            $order = Order::findOrFail($id);
            $order->delete();

            return response()->json([
                'message' => 'Order deleted successfully',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Order deleted failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Backward compatibility aliases
    public function index(Request $request) { return $this->order(); }
    public function show($id) { return $this->orderDetail($id); }
    public function store(Request $request) { return $this->createOrder($request); }
    public function update(Request $request, $id) { return $this->updateOrder($request, $id); }
    public function destroy($id) { return $this->deleteOrder($id); }
}
