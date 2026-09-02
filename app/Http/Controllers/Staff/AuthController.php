<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
        $request->validate([
            'name'         => 'required|string|max:255',
            'password'     => 'required|string|min:8',
            'staff_number' => 'required|string|max:50|unique:staff,staff_number',
            'salary'       => 'required|numeric|min:0',
            'age'          => 'required|integer|min:16|max:100',
            'type'         => ['required', Rule::in(['full_time', 'part_time'])],
            'image'        => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        return DB::transaction(function () use ($request) {
            // Extract request data excluding the raw image
            $fields = $request->except(['image']);

            // Process image upload into public/uploads/staff directory
            if ($request->hasFile('image')) {
                $uploadPath = public_path('uploads/staff');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $imageName);

                $fields['image'] = 'uploads/staff/' . $imageName;
            }

            $staff = Staff::create($fields);

            return response()->json([
                'message' => 'Staff created successfully',
                'data'    => $staff,
            ], 201);
        });
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
        $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'password'     => 'nullable|string|min:8',
            'staff_number' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('staff', 'staff_number')->ignore($staff->id)],
            'salary'       => 'sometimes|required|numeric|min:0',
            'age'          => 'sometimes|required|integer|min:16|max:100',
            'type'         => ['sometimes', 'required', Rule::in(['full_time', 'part_time'])],
            'image'        => 'sometimes|required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        return DB::transaction(function () use ($request, $staff) {
            $fields = $request->except(['image', 'password']);

            // Handle password updating if present
            if ($request->filled('password')) {
                $fields['password'] = $request->password;
            }

            // Process updated image upload
            if ($request->hasFile('image')) {
                $uploadPath = public_path('uploads/staff');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                // Delete old image file directly if it exists in public_path
                if ($staff->image && file_exists(public_path($staff->image))) {
                    @unlink(public_path($staff->image));
                }

                $image = $request->file('image');
                $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $imageName);

                $fields['image'] = 'uploads/staff/' . $imageName;
            }

            $staff->update($fields);

            return response()->json([
                'message' => 'Staff updated successfully',
                'data'    => $staff,
            ], 200);
        });
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy(Staff $staff)
    {
        if ($staff->image && file_exists(public_path($staff->image))) {
            @unlink(public_path($staff->image));
        }

        $staff->delete();

        return response()->json([
            'message' => 'Staff deleted successfully',
        ], 200);
    }
}