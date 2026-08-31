<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Handle staff login and issue Sanctum token.
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'staff_number' => 'required|string',
            'password'     => 'required|string',
        ]);

        $staff = Staff::where('staff_number', $fields['staff_number'])->first();

        // Verifies user existence and compares the plain text password against the hashed password
        if (!$staff || !Hash::check($fields['password'], $staff->password)) {
            return response()->json([
                'message' => 'Invalid staff credentials',
            ], 401);
        }

        $token = $staff->createToken('staff_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'data'    => $staff,
        ], 200);
    }

    /**
     * Display a listing of the staff.
     */
    public function index()
    {
        return response()->json(Staff::latest()->get(), 200);
    }

    /**
     * Store a newly created staff member.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name'            => 'required|string|max:255',
            'password'        => 'required|string|min:8',
            'guardian_number' => 'required|string|max:20',
            'staff_number'    => 'required|string|max:50|unique:staff,staff_number',
            'salary'          => 'required|numeric|min:0',
            'age'             => 'required|integer|min:16|max:100',
            'type'            => ['required', Rule::in(['full_time', 'part_time'])],
            'photo'           => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('staff_photos', 'public');
            $fields['photo'] = Storage::url($path);
        }

        $staff = Staff::create($fields);

        return response()->json([
            'message' => 'Staff created successfully',
            'data'    => $staff,
        ], 201);
    }

    /**
     * Display the specified staff member.
     */
    public function show(Staff $staff)
    {
        return response()->json($staff, 200);
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, Staff $staff)
    {
        $fields = $request->validate([
            'name'            => 'sometimes|required|string|max:255',
            'password'        => 'nullable|string|min:8',
            'guardian_number' => 'sometimes|required|string|max:20',
            'staff_number'    => ['sometimes', 'required', 'string', 'max:50', Rule::unique('staff', 'staff_number')->ignore($staff->id)],
            'salary'          => 'sometimes|required|numeric|min:0',
            'age'             => 'sometimes|required|integer|min:16|max:100',
            'type'            => ['sometimes', 'required', Rule::in(['full_time', 'part_time'])],
            'photo'           => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('photo')) {
            if ($staff->photo) {
                $oldPath = str_replace('/storage/', '', parse_url($staff->photo, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('photo')->store('staff_photos', 'public');
            $fields['photo'] = Storage::url($path);
        }

        if (empty($fields['password'])) {
            unset($fields['password']);
        }

        $staff->update($fields);

        return response()->json([
            'message' => 'Staff updated successfully',
            'data'    => $staff,
        ], 200);
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(Staff $staff)
    {
        if ($staff->photo) {
            $oldPath = str_replace('/storage/', '', parse_url($staff->photo, PHP_URL_PATH));
            Storage::disk('public')->delete($oldPath);
        }

        $staff->delete();

        return response()->json([
            'message' => 'Staff deleted successfully',
        ], 200);
    }
}