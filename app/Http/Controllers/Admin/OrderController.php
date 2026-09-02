<?php

namespace App\Http\Controllers\Admin;

use App\Models\User\Order;
use App\Models\Admin\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     * Filters: ?sell_by=name, ?order_number=ORD-..., ?search=query, ?status=pending|confirm|cancel
     */
    public function index(Request $request)
    {
        $query = Order::query();

        if ($request->filled('status')) {
            $query->where('order_status', $request->query('status'));
        }

        if ($request->filled('sell_by')) {
            $query->whereJsonContains('items', [['sell_by' => $request->query('sell_by')]]);
        }

        if ($request->filled('order_number')) {
            $query->where('order_number', 'like', '%' . $request->query('order_number') . '%');
        }

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
     * Store order (defaults to pending, revenue is NOT recorded until confirmed).
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
                'order_status'   => $request->input('order_status', 'pending'),
                'items'          => $validated['items'],
            ]);

            // Record revenue right away only if explicitly created as 'confirm'
            if ($order->order_status === 'confirm') {
                $this->syncRevenueReport($order);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Order created successfully with status: ' . $order->order_status,
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

    public function show(Order $order)
    {
        return response()->json([
            'status' => 'success',
            'data'   => $order,
        ], 200);
    }

    /**
     * Update order details.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $this->validateOrder($request);
        $totals = $this->calculateTotals($validated['items']);

        DB::beginTransaction();
        try {
            $newStatus = $request->input('order_status', $order->order_status);

            $order->update([
                'total_quantity' => $totals['total_quantity'],
                'total_discount' => $totals['total_discount'],
                'total_price'    => $totals['total_price'],
                'order_status'   => $newStatus,
                'items'          => $validated['items'],
            ]);

            // Sync revenue report according to updated status and price
            if ($order->order_status === 'confirm') {
                $this->syncRevenueReport($order);
            } else {
                $this->removeRevenueReport($order);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Order updated successfully',
                'data'    => $order,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Change order status directly (pending, confirm, cancel).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'order_status' => 'required|in:pending,confirm,cancel',
        ]);

        DB::beginTransaction();
        try {
            $order->order_status = $request->order_status;
            $order->save();

            if ($order->order_status === 'confirm') {
                $this->syncRevenueReport($order);
            } else {
                // If status changed back to pending or cancel, remove revenue from reports
                $this->removeRevenueReport($order);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => "Order status updated to '{$order->order_status}'",
                'data'    => $order,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to update order status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Order $order)
    {
        DB::beginTransaction();
        try {
            $this->removeRevenueReport($order);
            $order->delete();

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Order and associated revenue entry deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to delete order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to create or update report revenue entry.
     */
    private function syncRevenueReport(Order $order): void
    {
        Report::updateOrCreate(
            [
                'reference_type' => Order::class,
                'reference_id'   => $order->id,
            ],
            [
                'type'        => 'revenue',
                'category'    => 'order_sale',
                'amount'      => $order->total_price,
                'description' => "Revenue generated from confirmed Order #{$order->order_number}",
            ]
        );
    }

    /**
     * Helper to remove revenue entry if order cancelled/pending/deleted.
     */
    private function removeRevenueReport(Order $order): void
    {
        Report::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->delete();
    }

    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'order_status'                 => 'nullable|in:pending,confirm,cancel',
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