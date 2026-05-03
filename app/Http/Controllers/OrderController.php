<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // If admin, show all. If user, show only theirs.
        if ($request->user()->role === 'admin') {
            return response()->json(Order::with(['user', 'items.product'])->latest()->get());
        }

        return response()->json($request->user()->orders()->with('items.product')->latest()->get());
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order already processed'], 422);
        }

        return DB::transaction(function () use ($request, $order) {
            if ($request->status === 'approved') {
                foreach ($order->items as $item) {
                    $product = Product::lockForUpdate()->find($item->product_id);
                    if ($product->stock < $item->quantity) {
                        return response()->json([
                            'message' => "Insufficient stock for {$product->name}. Cannot approve."
                        ], 422);
                    }
                    $product->decrement('stock', $item->quantity);
                }
            }

            $order->update(['status' => $request->status]);

            // Create notification for the user
            $order->user->notifications()->create([
                'title' => $request->status === 'approved' ? 'Order Approved' : 'Order Rejected',
                'message' => $request->status === 'approved' 
                    ? "Great news! Your order #{$order->id} was approved and items are on their way." 
                    : "Sorry, your order #{$order->id} was rejected. No charges were made.",
                'type' => $request->status === 'approved' ? 'success' : 'error',
            ]);

            return response()->json([
                'message' => "Order {$request->status} successfully",
                'order' => $order->load('items.product')
            ]);
        });
    }

    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $totalPrice = 0;
            $orderItemsData = [];

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'message' => "Sorry, {$product->name} is currently out of stock or low."
                    ], 422);
                }

                $totalPrice += $product->price * $item['quantity'];
                
                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ];
            }

            $order = Order::create([
                'user_id' => $request->user()->id,
                'total_price' => $totalPrice,
                'status' => 'pending',
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);
            }

            return response()->json([
                'message' => 'Order submitted for approval',
                'order' => $order->load('items.product'),
            ], 201);
        });
    }
}
