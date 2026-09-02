<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin\Product;
use App\Models\Admin\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // Display a listing of products with their associated category
    public function index()
    {
        $products = Product::with('category')->latest()->get();
        return response()->json($products);
    }

    // Store a newly created product & record cost in reports table
    public function store(Request $request)
    {
        $request->validate([
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

        return DB::transaction(function () use ($request) {
            // 1. Extract inputs except files
            $data = $request->except(['images']);

            // 2. Process file uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                $uploadPath = public_path('uploads/products');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                foreach ($request->file('images') as $image) {
                    $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    $image->move($uploadPath, $imageName);
                    $imagePaths[] = 'uploads/products/' . $imageName;
                }
            }

            // 3. Generate slug and attach image paths array
            $data['slug'] = Str::slug($data['name']);
            $data['images'] = $imagePaths;

            // 4. Create record in products table
            $product = Product::create($data);

            // 5. Calculate total cost and create Report record (if quantity > 0)
            $totalCost = round((float)$product->buying_price * (int)$product->quantity, 2);

            if ($totalCost > 0) {
                Report::create([
                    'type'           => 'cost',
                    'category'       => 'product_addition',
                    'amount'         => $totalCost,
                    'reference_type' => Product::class,
                    'reference_id'   => $product->id,
                    'description'    => "Initial stock purchase for '{$product->name}' ({$product->quantity} units @ {$product->buying_price} each)",
                ]);
            }

            return response()->json($product->load('category'), 201);
        });
    }

    // Display a single product
    public function show(Product $product)
    {
        return response()->json($product->load('category'));
    }

    // Update an existing product
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'category_id'   => 'required|exists:categories,id',
            'name'          => 'required|string|max:255',
            'sku'           => 'nullable|string|max:100|unique:products,sku,' . $product->id,
            'quantity'      => 'required|integer|min:0',
            'buying_price'  => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'location'      => 'required|string|max:255',
            'notes'         => 'nullable|string',
            'images'        => 'nullable|array|max:3',
            'images.*'      => 'nullable|file|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        return DB::transaction(function () use ($request, $product) {
            $data = $request->except(['images', 'existing_images']);

            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle retained existing images passed from frontend
            $retainedImages = $request->input('existing_images', []);
            $currentImages = is_array($product->images) ? $product->images : [];

            // Identify and delete removed images directly from public directory
            $imagesToDelete = array_diff($currentImages, $retainedImages);
            foreach ($imagesToDelete as $imageToDelete) {
                $filePath = public_path($imageToDelete);
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }
            }

            // Upload and process newly added image files
            $newImagePaths = [];
            if ($request->hasFile('images')) {
                $uploadPath = public_path('uploads/products');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                foreach ($request->file('images') as $image) {
                    $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    $image->move($uploadPath, $imageName);
                    $newImagePaths[] = 'uploads/products/' . $imageName;
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

            $data['images'] = $finalImages;

            $product->update($data);

            return response()->json($product->load('category'));
        });
    }

    // Delete a product and its associated report entries
    public function destroy(Product $product)
    {
        return DB::transaction(function () use ($product) {
            // Delete stored image files directly from public directory
            if ($product->images && is_array($product->images)) {
                foreach ($product->images as $image) {
                    $filePath = public_path($image);
                    if (file_exists($filePath) && is_file($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            // Delete product initial cost entry from reports table
            Report::where('reference_type', Product::class)
                ->where('reference_id', $product->id)
                ->delete();

            $product->delete();

            return response()->json(['message' => 'Product and related cost reports deleted successfully']);
        });
    }
}