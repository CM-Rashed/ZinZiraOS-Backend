<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Product;
class UserProductController extends Controller
{
     public function index()
    {
        $products = Product::with('category')->latest()->take(10)->get();
        return response()->json($products);
    }
    // Display a single product
    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }
}
