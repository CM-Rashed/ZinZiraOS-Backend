<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\UserOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
class UserOrderController extends Controller
{
   public function index(Request $request): JsonResponse
    {
        $orders = UserOrder::query()
            ->when($request->user(), function ($query, $user) {
                return $query->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data'   => $orders,
        ], 200);
    }

    /**
     * Store a newly created website user order.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateOrder($request);
        $totals = $this->calculateTotals($validated['items']);

        $order = UserOrder::create([
            'order_number'     => 'ORD-' . strtoupper(Str::random(10)),
            'user_id'          => $request->user()?->id,
            'name'             => $validated['name'],
            'phone'            => $validated['phone'],
            'delivery_address' => $validated['delivery_address'],
            'paid_by'          => $validated['paid_by'],
            'payment_status'   => $validated['paid_by'] === 'online' ? 'paid' : 'pending',
            'order_status'     => 'pending',
            'notes'            => $validated['notes'] ?? null,
            'total_quantity'   => $totals['total_quantity'],
            'total_discount'   => $totals['total_discount'],
            'total_price'      => $totals['total_price'],
            'items'            => $validated['items'],
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order created successfully',
            'data'    => $order,
        ], 201);
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, UserOrder $order): JsonResponse
    {
        if ($request->user() && $order->user_id && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $order,
        ], 200);
    }

    /**
     * Cancel an active order.
     */
    public function cancel(Request $request, UserOrder $order): JsonResponse
    {
        if ($request->user() && $order->user_id && $order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->order_status !== 'pending') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only pending orders can be cancelled',
            ], 422);
        }

        $order->update(['order_status' => 'cancelled']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Order cancelled successfully',
            'data'    => $order,
        ], 200);
    }

    /**
     * Validate incoming order request.
     */
    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'name'                         => 'required|string|max:255',
            'phone'                        => 'required|string|max:20',
            'delivery_address'             => 'required|string|max:500',
            'paid_by'                      => 'required|string|in:cash,cod,online',
            'notes'                        => 'nullable|string|max:1000',
            'items'                        => 'required|array|min:1',
            'items.*.product_id'           => 'required|integer',
            'items.*.products_name'        => 'required|string|max:255',
            'items.*.products_price'       => 'required|numeric|min:0',
            'items.*.products_discount'    => 'nullable|numeric|min:0',
            'items.*.products_quantity'    => 'required|integer|min:1',
            'items.*.products_total_price' => 'required|numeric|min:0',
            'items.*.sell_by'              => 'required|string|max:50',
        ]);
    }

    /**
     * Calculate order totals.
     */
    private function calculateTotals(array $items): array
    {
        $collection = collect($items);

        return [
            'total_quantity' => $collection->sum('products_quantity'),
            'total_discount' => $collection->sum(function ($item) {
                return ($item['products_discount'] ?? 0) * $item['products_quantity'];
            }),
            'total_price'    => $collection->sum('products_total_price'),
        ];
    }
}
