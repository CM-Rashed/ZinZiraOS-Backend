<?php

namespace App\Http\Controllers\Admin;

use App\Models\User\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateOrder($request);
        $totals = $this->calculateTotals($validated['items']);

        $order = Order::create([
            'order_number'   => 'ORD-' . strtoupper(Str::random(10)),
            'total_quantity' => $totals['total_quantity'],
            'total_discount' => $totals['total_discount'],
            'total_price'    => $totals['total_price'],
            'items'          => $validated['items'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order created successfully',
            'data'    => $order,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $order,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $this->validateOrder($request);
        $totals = $this->calculateTotals($validated['items']);

        $order->update([
            'total_quantity' => $totals['total_quantity'],
            'total_discount' => $totals['total_discount'],
            'total_price'    => $totals['total_price'],
            'items'          => $validated['items'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order updated successfully',
            'data'    => $order,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Order deleted successfully',
        ], 200);
    }

    /**
     * Helper to validate incoming payload.
     */
    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'items'                        => 'required|array|min:1',
            'items.*.product_id'           => 'required|integer',
            'items.*.products_name'        => 'required|string',
            'items.*.products_price'       => 'required|numeric|min:0',
            'items.*.products_discount'    => 'nullable|numeric|min:0',
            'items.*.products_quantity'    => 'required|integer|min:1',
            'items.*.products_total_price' => 'required|numeric|min:0',
            'items.*.sell_by'              => 'required|string',
        ]);
    }

    /**
     * Helper to calculate aggregate order metrics.
     */
    private function calculateTotals(array $items): array
    {
        $collection = collect($items);

        return [
            'total_quantity' => $collection->sum('products_quantity'),
            'total_discount' => $collection->sum(function ($item) {
                return ($item['products_discount'] ?? 0) * $item['products_quantity'];
            }),
            'total_price' => $collection->sum('products_total_price'),
        ];
    }
}
