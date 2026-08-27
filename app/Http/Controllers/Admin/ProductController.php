<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    // Display a listing of products with optional status & low stock filters
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category');

        // Filter: Low stock alerts
        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'alert_quantity');
        }

        // Filter: Active / Inactive status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter: Warehouse Location
        if ($request->filled('location')) {
            $query->where('location', $request->input('location'));
        }

        $products = $query->latest()->get();

        return response()->json($products);
    }

    // Store a newly created product
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id'    => 'required|exists:categories,id',
            'name'           => 'required|string|max:255',
            'sku'            => 'nullable|string|max:100|unique:products,sku',
            'quantity'       => 'required|integer|min:0',
            'alert_quantity' => 'nullable|integer|min:0',
            'unit'           => 'nullable|string|max:50',
            'buying_price'   => 'required|numeric|min:0',
            'selling_price'  => 'required|numeric|min:0',
            'location'       => 'nullable|string|max:255',
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $validated['slug'] = Str::slug($validated['name']);
        $validated['unit'] = $validated['unit'] ?? 'pcs';
        $validated['alert_quantity'] = $validated['alert_quantity'] ?? 5;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $product = Product::create($validated);

        return response()->json($product->load('category'), 201);
    }

    // Display a single product
    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load('category'));
    }

    // Update an existing product
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'category_id'    => 'sometimes|required|exists:categories,id',
            'name'           => 'sometimes|required|string|max:255',
            'sku'            => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'quantity'       => 'sometimes|required|integer|min:0',
            'alert_quantity' => 'nullable|integer|min:0',
            'unit'           => 'nullable|string|max:50',
            'buying_price'   => 'sometimes|required|numeric|min:0',
            'selling_price'  => 'sometimes|required|numeric|min:0',
            'location'       => 'nullable|string|max:255',
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Upload new image and delete previous image from storage
        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return response()->json($product->load('category'));
    }

    // Quick Inventory Adjustment (Stock In, Stock Out, or Set Absolute Count)
    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'action'   => 'required|in:add,subtract,set',
            'quantity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validated['action'] === 'add') {
            $product->increment('quantity', $validated['quantity']);
        } elseif ($validated['action'] === 'subtract') {
            if ($product->quantity < $validated['quantity']) {
                return response()->json([
                    'message' => 'Insufficient stock! Current stock is ' . $product->quantity,
                ], 422);
            }
            $product->decrement('quantity', $validated['quantity']);
        } elseif ($validated['action'] === 'set') {
            $product->update(['quantity' => $validated['quantity']]);
        }

        if (!empty($validated['location'])) {
            $product->update(['location' => $validated['location']]);
        }

        return response()->json([
            'message' => 'Stock updated successfully',
            'product' => $product->fresh()->load('category'),
        ]);
    }

    // Toggle product active/inactive status
    public function toggleStatus(Product $product): JsonResponse
    {
        $product->update(['is_active' => !$product->is_active]);

        return response()->json([
            'message'   => 'Product status updated',
            'is_active' => $product->is_active,
        ]);
    }

    // Soft delete a product
    public function destroy(Product $product): JsonResponse
    {
        // For soft deletes, keep the image on disk so restored products remain intact
        $product->delete();

        return response()->json(['message' => 'Product archived successfully']);
    }

    // Permanently remove a product and its image
    public function forceDelete($id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->forceDelete();

        return response()->json(['message' => 'Product permanently deleted']);
    }
}