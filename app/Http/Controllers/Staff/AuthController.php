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
     * Display a listing of the staff.
     */
    public function index()
    {
        $staff = Staff::latest()->get();

        return response()->json($staff, 200);
    }

    /**
     * Store a newly created staff member in storage.
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

        $photoUrl = null;
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('staff_photos', 'public');
            $photoUrl = Storage::url($path);
        }

        $staff = Staff::create([
            'name'            => $fields['name'],
            'password'        => Hash::make($fields['password']),
            'guardian_number' => $fields['guardian_number'],
            'staff_number'    => $fields['staff_number'],
            'salary'          => $fields['salary'],
            'age'             => $fields['age'],
            'type'            => $fields['type'],
            'photo'           => $photoUrl,
        ]);

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
     * Update the specified staff member in storage.
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

        // Handle new photo upload and delete old file if it exists
        if ($request->hasFile('photo')) {
            if ($staff->photo) {
                $oldPath = str_replace('/storage/', '', parse_url($staff->photo, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('photo')->store('staff_photos', 'public');
            $fields['photo'] = Storage::url($path);
        }

        // Only update password if a new one is explicitly provided
        if (!empty($fields['password'])) {
            $fields['password'] = Hash::make($fields['password']);
        } else {
            unset($fields['password']);
        }

        $staff->update($fields);

        return response()->json([
            'message' => 'Staff updated successfully',
            'data'    => $staff,
        ], 200);
    }

    /**
     * Remove the specified staff member from storage.
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
