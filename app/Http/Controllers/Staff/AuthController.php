<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Staff\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | STAFF REGISTRATION FLOW (2-STEP OTP)
    |--------------------------------------------------------------------------
    */

    /**
     * Step 1 (Register): Validate form and send OTP code to staff email.
     */
    public function requestRegistrationOtp(Request $request)
    {
        $fields = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:staff,email',
            'staff_number' => 'required|string|max:50|unique:staff,staff_number',
            'password'     => 'required|string|min:8|confirmed',
            'salary'       => 'required|numeric|min:0',
            'age'          => 'required|integer|min:16|max:100',
            'type'         => ['required', Rule::in(['full_time', 'part_time'])],
        ]);

        $otp = (string) random_int(100000, 999999);

        // Store staff registration details and OTP in Cache for 10 minutes
        Cache::put('staff_reg_otp_' . $fields['email'], [
            'otp'          => $otp,
            'name'         => $fields['name'],
            'email'        => $fields['email'],
            'staff_number' => $fields['staff_number'],
            'password'     => $fields['password'],
            'salary'       => $fields['salary'],
            'age'          => $fields['age'],
            'type'         => $fields['type'],
        ], now()->addMinutes(10));

        try {
            Mail::to($fields['email'])->send(new OtpMail($otp));
        } catch (\Exception $e) {
            // Handle mail exception if necessary
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Verification OTP sent to staff email.',
        ], 200);
    }

    /**
     * Step 2 (Register): Verify OTP and create staff account with image.
     */
    public function completeRegistration(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string|email',
            'otp'   => 'required|string|size:6',
            'image' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $cachedData = Cache::get('staff_reg_otp_' . $fields['email']);

        if (!$cachedData || $cachedData['otp'] !== $fields['otp']) {
            return response()->json([
                'message' => 'Invalid or expired OTP code.'
            ], 422);
        }

        if (Staff::where('email', $fields['email'])->exists()) {
            return response()->json([
                'message' => 'Staff record already exists.'
            ], 409);
        }

        return DB::transaction(function () use ($request, $cachedData, $fields) {
            $imagePath = null;

            // Handle image upload passed with OTP verification request
            if ($request->hasFile('image')) {
                $uploadPath = public_path('uploads/staff');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                $image = $request->file('image');
                $imageName = time() . '_image_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $imageName);

                $imagePath = 'uploads/staff/' . $imageName;
            }

            // Create verified staff member
            $staff = Staff::create([
                'name'              => $cachedData['name'],
                'email'             => $cachedData['email'],
                'staff_number'      => $cachedData['staff_number'],
                'password'          => Hash::make($cachedData['password']),
                'salary'            => $cachedData['salary'],
                'age'               => $cachedData['age'],
                'type'              => $cachedData['type'],
                'image'             => $imagePath,
                'email_verified_at' => now(),
            ]);

            Cache::forget('staff_reg_otp_' . $fields['email']);

            $token = $staff->createToken('staff_token', ['role:staff'])->plainTextToken;

            return response()->json([
                'status'  => 'success',
                'data'    => $staff,
                'token'   => $token,
                'message' => 'Staff registered successfully.'
            ], 201);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | STAFF LOGIN FLOW (2-STEP OTP)
    |--------------------------------------------------------------------------
    */

    /**
     * Step 1 (Login): Validate credentials & dispatch 2FA OTP.
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

        $otp = (string) random_int(100000, 999999);

        // Store OTP indexed by staff_number in Cache for 10 minutes
        Cache::put('staff_login_otp_' . $staff->staff_number, $otp, now()->addMinutes(10));

        if (!empty($staff->email)) {
            try {
                Mail::to($staff->email)->send(new OtpMail($otp));
            } catch (\Exception $e) {
                // Handle mail exception if necessary
            }
        }

        return response()->json([
            'requires_otp' => true,
            'staff_number' => $staff->staff_number,
            'message'      => 'A 6-digit security code was sent to your registered email.',
        ], 200);
    }

    /**
     * Step 2 (Login): Verify OTP and issue token.
     */
    public function completeLogin(Request $request)
    {
        $fields = $request->validate([
            'staff_number' => 'required|string',
            'otp'          => 'required|string|size:6',
        ]);

        $staff = Staff::where('staff_number', $fields['staff_number'])->first();

        if (!$staff) {
            return response()->json(['message' => 'Staff record not found.'], 404);
        }

        $cachedOtp = Cache::get('staff_login_otp_' . $staff->staff_number);

        if (!$cachedOtp || $cachedOtp !== $fields['otp']) {
            return response()->json([
                'message' => 'Invalid or expired security code.'
            ], 422);
        }

        Cache::forget('staff_login_otp_' . $staff->staff_number);

        $token = $staff->createToken('staff_token', ['role:staff'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token'   => $token,
            'data'    => $staff,
        ], 200);
    }

    /**
     * Logout staff session.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /*
    |--------------------------------------------------------------------------
    | CRUD ACTIONS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return response()->json(Staff::latest()->get(), 200);
    }

    /**
     * Store new staff record directly with file upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:staff,email',
            'password'     => 'required|string|min:8',
            'staff_number' => 'required|string|max:50|unique:staff,staff_number',
            'salary'       => 'required|numeric|min:0',
            'age'          => 'required|integer|min:16|max:100',
            'type'         => ['required', Rule::in(['full_time', 'part_time'])],
            'image'        => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. Extract non-file inputs and hash password
            $data = $request->except(['image']);
            $data['password'] = Hash::make($request->password);

            // 2. Process file upload matching Product methodology
            if ($request->hasFile('image')) {
                $uploadPath = public_path('uploads/staff');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                $image = $request->file('image');
                $imageName = time() . '_image_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $imageName);

                $data['image'] = 'uploads/staff/' . $imageName;
            }

            // 3. Create staff record
            $staff = Staff::create($data);

            return response()->json([
                'message' => 'Staff created successfully',
                'data'    => $staff,
            ], 201);
        });
    }

    public function show(Staff $staff)
    {
        return response()->json($staff, 200);
    }

    /**
     * Update existing staff record with file upload.
     */
    public function update(Request $request, Staff $staff)
    {
        $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'email'        => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('staff', 'email')->ignore($staff->id)],
            'password'     => 'nullable|string|min:8',
            'staff_number' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('staff', 'staff_number')->ignore($staff->id)],
            'salary'       => 'sometimes|required|numeric|min:0',
            'age'          => 'sometimes|required|integer|min:16|max:100',
            'type'         => ['sometimes', 'required', Rule::in(['full_time', 'part_time'])],
            'image'        => 'nullable|file|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        return DB::transaction(function () use ($request, $staff) {
            // 1. Extract non-file inputs
            $data = $request->except(['image', 'password']);

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            // 2. Process replacement file upload matching Product methodology
            if ($request->hasFile('image')) {
                $uploadPath = public_path('uploads/staff');

                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                // Remove existing image file if present
                if (!empty($staff->image)) {
                    $oldFilePath = public_path($staff->image);
                    if (file_exists($oldFilePath) && is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }

                $image = $request->file('image');
                $imageName = time() . '_image_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $image->move($uploadPath, $imageName);

                $data['image'] = 'uploads/staff/' . $imageName;
            }

            // 3. Update staff record
            $staff->update($data);

            return response()->json([
                'message' => 'Staff updated successfully',
                'data'    => $staff,
            ], 200);
        });
    }

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