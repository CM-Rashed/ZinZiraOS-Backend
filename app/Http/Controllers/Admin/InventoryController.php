<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Product;
use App\Models\Admin\Inventory;
use App\Models\Admin\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller {
    public function store(Request $request) {
        $validated = $request->validate([
            'product_id'     => 'required|exists:products,id',
            'added_quantity' => 'required|integer|min:1',
            'unit_cost'      => 'nullable|numeric|min:0', // Accepts unit cost from frontend
            'notes'          => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $product = Product::findOrFail($validated['product_id']);
            
            $previousQty = $product->quantity;
            $newQty = $previousQty + $validated['added_quantity'];

            // 1. Update Product quantity
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

            // 3. Resolve Unit Cost
            // Check request first, then fallback to product object values dynamically
            $unitCost = $request->input('unit_cost');

            if ($unitCost === null || $unitCost === '') {
                // Loop through all product database attributes to find any numerical price column
                foreach ($product->getAttributes() as $columnName => $value) {
                    if (is_numeric($value) && $value > 0 && str_contains(strtolower($columnName), 'price')) {
                        $unitCost = $value;
                        break;
                    }
                }
            }

            // Final fallback to 0 if no cost found anywhere
            $unitCost = (float)($unitCost ?? 0);
            $totalCost = round($unitCost * (int)$validated['added_quantity'], 2);

            // 4. Create Report Record
            $report = Report::create([
                'type'           => 'cost',
                'category'       => 'inventory_restock',
                'amount'         => $totalCost,
                'reference_type' => Inventory::class,
                'reference_id'   => $inventory->id,
                'description'    => "Restocked {$validated['added_quantity']} units of '{$product->name}' (Product ID: {$product->id})",
            ]);

            return response()->json([
                'message'   => 'Inventory updated successfully',
                'product'   => $product->load('category'),
                'inventory' => $inventory,
                'report'    => $report,
            ], 200);
        });
    }
}