<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\Product;
class ProductController extends Controller
{
        public function index()
    {
        $products = Product::with('category')->latest()->get();
        return response()->json($products);
    }
}
