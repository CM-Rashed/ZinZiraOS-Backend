<?php

namespace App\Http\Controllers\Admin;

use App\Models\User\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * Supports search filters: ?sell_by=name, ?order_number=ORD-..., ?search=query
     */
    public function index(Request $request)
    {
        $query = Order::query();

        // Filter directly by staff name in JSON items array
        if ($request->filled('sell_by')) {
            $query->whereJsonContains('items', [['sell_by' => $request->query('sell_by')]]);
        }

        // Filter by exact or partial order number
        if ($request->filled('order_number')) {
            $query->where('order_number', 'like', '%' . $request->query('order_number') . '%');
        }

        // General search across order number or staff name
        if ($request->filled('search')) {
            $searchTerm = $request->query('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhereJsonContains('items', [['sell_by' => $searchTerm]]);
            });
        }

        $orders = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'count'  => $orders->count(),
            'data'   => $orders,
        ], 200);
    }

    /**
     * Get all orders sold by a specific staff member.
     */
 public function byStaff(Request $request, $staff_name = null)
{
    $staffName = $staff_name ?? $request->query('sell_by');

    if (!$staffName) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Staff name (sell_by) is required.',
        ], 422);
    }

    $orders = Order::whereJsonContains('items', [['sell_by' => $staffName]])
        ->latest()
        ->get();

    return response()->json([
        'status'     => 'success',
        'staff_name' => $staffName,
        'count'      => $orders->count(),
        'data'       => $orders,
    ], 200);
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateOrder($request);
        $totals = $this->calculateTotals($validated['items']);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'order_number'   => 'ORD-' . strtoupper(Str::random(10)),
                'total_quantity' => $totals['total_quantity'],
                'total_discount' => $totals['total_discount'],
                'total_price'    => $totals['total_price'],
                'items'          => $validated['items'],
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Order created successfully',
                'data'    => $order,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
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
     * Helper to calculate aggregate order metrics accurately.
     */
    private function calculateTotals(array $items): array
    {
        $collection = collect($items);

        $totalQuantity = $collection->sum('products_quantity');
        
        $totalDiscount = $collection->sum(function ($item) {
            $discount = isset($item['products_discount']) ? (float)$item['products_discount'] : 0;
            $qty = (int)$item['products_quantity'];
            return $discount * $qty;
        });

        $totalPrice = $collection->sum(function ($item) {
            return (float)$item['products_total_price'];
        });

        return [
            'total_quantity' => $totalQuantity,
            'total_discount' => round($totalDiscount, 2),
            'total_price'    => round($totalPrice, 2),
        ];
    }
}