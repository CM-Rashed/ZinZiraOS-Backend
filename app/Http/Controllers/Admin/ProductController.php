<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    // Display a listing of products with their associated category
    public function index()
    {
        $products = Product::with('category')->latest()->get();
        return response()->json($products);
    }

    // Store a newly created product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'name'          => 'required|string|max:255',
            'sku'           => 'nullable|string|max:100|unique:products,sku',
            'quantity'      => 'required|integer|min:0',
            'buying_price'  => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'location'      => 'required|string|max:255',
            'notes'         => 'nullable|string',
            'images'        => 'required|array|min:1|max:3',
            'images.*'      => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $imagePaths[] = $image->store('products', 'public');
            }
        }

        $validated['slug'] = Str::slug($validated['name']);
        $validated['images'] = $imagePaths;

        $product = Product::create($validated);

        return response()->json($product->load('category'), 201);
    }

    // Display a single product
    public function show(Product $product)
    {
        return response()->json($product->load('category'));
    }

    // Update an existing product
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id'     => 'sometimes|required|exists:categories,id',
            'name'            => 'sometimes|required|string|max:255',
            'sku'             => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'quantity'        => 'sometimes|required|integer|min:0',
            'buying_price'    => 'sometimes|required|numeric|min:0',
            'selling_price'   => 'sometimes|required|numeric|min:0',
            'location'        => 'sometimes|required|string|max:255',
            'notes'           => 'nullable|string',
            'existing_images' => 'nullable|array',
            'existing_images.*' => 'string',
            'images'          => 'nullable|array',
            'images.*'        => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Handle retained existing images passed from frontend
        $retainedImages = $request->input('existing_images', []);
        $currentImages = is_array($product->images) ? $product->images : [];

        // Identify and delete removed images from physical storage
        $imagesToDelete = array_diff($currentImages, $retainedImages);
        foreach ($imagesToDelete as $imageToDelete) {
            Storage::disk('public')->delete($imageToDelete);
        }

        // Upload and process newly added image files
        $newImagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $newImagePaths[] = $image->store('products', 'public');
            }
        }

        // Merge retained images with newly uploaded images
        $finalImages = array_values(array_merge($retainedImages, $newImagePaths));

        // Validate 1 to 3 images restriction
        if (count($finalImages) < 1 || count($finalImages) > 3) {
            return response()->json([
                'message' => 'The product must have between 1 and 3 images.'
            ], 422);
        }

        $validated['images'] = $finalImages;

        // Clean up temporary keys before database update
        unset($validated['existing_images']);

        $product->update($validated);

        return response()->json($product->load('category'));
    }

    // Delete a product
    public function destroy(Product $product)
    {
        // Delete stored image files from disk before deleting the database record
        if ($product->images && is_array($product->images)) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}