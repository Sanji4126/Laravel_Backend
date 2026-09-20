<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function payment()
    {
        try {
            $user = auth('api')->user();

            if ($user->role === 'admin') {
                $payments = Payment::with('order.user')->latest()->get();
            } else {
                $payments = Payment::whereHas('order', function ($q) use ($user) {
                    $q->where('user_id', $user->user_id);
                })->with('order')->latest()->get();
            }

            return response()->json([
                'message' => 'get data successfully',
                'payment' => $payments,
                'payments' => $payments,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'get payments failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentDetail($id)
    {
        try {
            $user = auth('api')->user();
            $payment = Payment::with('order.user')->findOrFail($id);

            if ($user->role !== 'admin' && $payment->order->user_id !== $user->user_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json([
                'message' => 'get data successfully',
                'payment' => $payment,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Payment not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    public function createPayment(Request $request)
    {
        try {
            $data = $request->validate([
                'order_id' => 'required|exists:orders,order_id',
                'payment_method' => 'required|string|max:50',
                'amount' => 'required|numeric|min:0.01',
                'transaction_id' => 'nullable|string|max:100',
            ]);

            $user = auth('api')->user();
            $order = Order::findOrFail($data['order_id']);

            if ($user->role !== 'admin' && $order->user_id !== $user->user_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $payment = Payment::create([
                'order_id' => $order->order_id,
                'payment_method' => $data['payment_method'],
                'transaction_id' => $request->input('transaction_id') ?? ('TXN-' . strtoupper(Str::random(12))),
                'amount' => $data['amount'],
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            $totalPaid = Payment::where('order_id', $order->order_id)
                ->where('status', 'completed')
                ->sum('amount');

            if ($totalPaid >= $order->total && $order->status === 'pending') {
                $order->update(['status' => 'processing']);
            }

            return response()->json([
                'message' => 'Payment created successfully',
                'payment' => $payment,
                'order' => $order->fresh(),
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Payment created failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Backward compatibility aliases
    public function index(Request $request) { return $this->payment(); }
    public function show($id) { return $this->paymentDetail($id); }
    public function store(Request $request) { return $this->createPayment($request); }
}
