<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Product;
use App\Models\Admin\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller {
    // Add quantity to product stock and log transaction
    public function store(Request $request) {
        $validated = $request->validate([
            'product_id'     => 'required|exists:products,id',
            'added_quantity' => 'required|integer|min:1',
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $product = Product::findOrFail($validated['product_id']);
            
            $previousQty = $product->quantity;
            $newQty = $previousQty + $validated['added_quantity'];

            // 1. Update total quantity & append notes on the product
            $product->quantity = $newQty;
            if (!empty($validated['notes'])) {
                $product->notes = $validated['notes'];
            }
            $product->save();

            // 2. Log entry in inventories table
            $inventory = Inventory::create([
                'product_id'        => $product->id,
                'added_quantity'   => $validated['added_quantity'],
                'previous_quantity' => $previousQty,
                'new_quantity'      => $newQty,
                'notes'             => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'message'   => 'Inventory updated successfully',
                'product'   => $product->load('category'),
                'inventory' => $inventory,
            ], 200);
        });
    }
}
