<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
class AuthController extends Controller
{
    public function register(Request $request)
    {
        $fields = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:staff,email',
            'password' => 'required|string|min:8|confirmed',
            'age'      => 'required|integer|min:18|max:100',
            'mobile'   => 'required|string|max:20',
            'salary'   => 'required|numeric|min:0',
            'photo'    => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Optional photo up to 2MB
        ]);

        // Handle Photo Upload if present
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('staff_photos', 'public');
        }

        $staff = Staff::create([
            'name'     => $fields['name'],
            'email'    => $fields['email'],
            'password' => Hash::make($fields['password']),
            'age'      => $fields['age'],
            'mobile'   => $fields['mobile'],
            'salary'   => $fields['salary'],
            'photo'    => $photoPath ? Storage::url($photoPath) : null,
        ]);

        $token = $staff->createToken('staff-token', ['role:staff'])->plainTextToken;

        return response()->json([
            'staff' => $staff,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $fields = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $staff = Staff::where('email', $fields['email'])->first();

        if (!$staff || !Hash::check($fields['password'], $staff->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $staff->createToken('staff-token', ['role:staff'])->plainTextToken;

        return response()->json([
            'staff' => $staff,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
