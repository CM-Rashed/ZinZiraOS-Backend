<?php

namespace App\Http\Controllers\Customer;

use App\Models\Customer\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
class CustomerController extends Controller
{
    // GET /api/customers
    public function index(): JsonResponse
    {
        $customers = Customer::latest()->get();
        
        return response()->json([
            'success' => true,
            'data' => $customers
        ], 200);
    }

    // POST /api/customers
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'due' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer created successfully.',
            'data' => $customer
        ], 201);
    }

    // GET /api/customers/{customer}
    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $customer
        ], 200);
    }

    // PUT/PATCH /api/customers/{customer}
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'due' => 'nullable|numeric|min:0',
        ]);

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully.',
            'data' => $customer
        ], 200);
    }

    // DELETE /api/customers/{customer}
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully.'
        ], 200);
    }
}
