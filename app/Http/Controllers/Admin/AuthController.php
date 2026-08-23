<?php 

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'admin_name'    => 'required|string|max:255',
            'admin_number'  => 'required|string|unique:admins,admin_number',
            'email'         => 'nullable|string|email|max:255|unique:admins,email',
            'password'      => 'required|string|min:8|confirmed',
            'shop_name'     => 'required|string|max:255',
            'shop_location' => 'required|string|max:255',
            'staff_numbers' => 'required|integer|min:0',
            'shop_type'     => ['required', Rule::in(['grocery', 'supermarket', 'library', 'telecom'])],
        ]);

        $admin = Admin::create([
            'admin_name'    => $fields['admin_name'],
            'admin_number'  => $fields['admin_number'],
            'email'         => $fields['email'] ?? null,
            'password'      => Hash::make($fields['password']),
            'shop_name'     => $fields['shop_name'],
            'shop_location' => $fields['shop_location'],
            'staff_numbers' => $fields['staff_numbers'],
            'shop_type'     => $fields['shop_type'],
        ]);

        $token = $admin->createToken('admin-token', ['role:admin'])->plainTextToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'login'    => 'required|string', // Accepts either email or admin_number
            'password' => 'required|string',
        ]);

        // Search by email OR admin_number
        $admin = Admin::where('email', $fields['login'])
            ->orWhere('admin_number', $fields['login'])
            ->first();

        if (!$admin || !Hash::check($fields['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin-token', ['role:admin'])->plainTextToken;

        return response()->json([
            'admin' => $admin,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}