<?php 

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Admin;
use App\Utils\OtpUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | REGISTRATION WITH OTP FLOW
    |--------------------------------------------------------------------------
    */

    /**
     * Step 1 (Register): Validate input, stash payload in Cache, send OTP email.
     */
    public function requestRegistrationOtp(Request $request)
    {
        $fields = $request->validate([
            'admin_name'    => 'required|string|max:255',
            'admin_number'  => 'required|string|unique:admins,admin_number',
            'email'         => 'required|string|email|max:255|unique:admins,email',
            'password'      => 'required|string|min:8|confirmed',
            'shop_name'     => 'required|string|max:255',
            'shop_location' => 'required|string|max:255',
            'staff_numbers' => 'required|integer|min:0',
            'shop_type'     => ['required', Rule::in(['grocery', 'supermarket', 'library', 'telecom'])],
        ]);

        // Hash password before stashing in cache
        $fields['password'] = Hash::make($fields['password']);

        // Stash data and send OTP email
        OtpUtil::sendRegistrationOtp($fields['email'], $fields);

        return response()->json([
            'message' => 'OTP sent to your email. Verify OTP to complete account creation.',
            'email'   => $fields['email'],
        ]);
    }

    /**
     * Step 2 (Register): Verify OTP, fetch cached registration data, save Admin to DB.
     */
    public function completeRegistration(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|numeric',
        ]);

        // Verify OTP and retrieve stashed payload
        $pendingData = OtpUtil::verifyAndRetrieveData($request->email, $request->otp);

        if (!$pendingData) {
            return response()->json([
                'message' => 'Invalid or expired OTP code.'
            ], 422);
        }

        // Set email as verified upon creation
        $pendingData['email_verified_at'] = now();

        // Create Admin record in database
        $admin = Admin::create($pendingData);

        // Issue access token
        $token = $admin->createToken('admin-token', ['role:admin'])->plainTextToken;

        return response()->json([
            'message' => 'Account created and verified successfully.',
            'admin'   => $admin,
            'token'   => $token,
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN WITH OTP FLOW (2FA)
    |--------------------------------------------------------------------------
    */

    /**
     * Step 1 (Login): Verify password credentials and dispatch Login OTP.
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'login'    => 'required|string', // Accepts email or admin_number
            'password' => 'required|string',
        ]);

        // Search Admin by email or admin_number
        $admin = Admin::where('email', $fields['login'])
            ->orWhere('admin_number', $fields['login'])
            ->first();

        if (!$admin || !Hash::check($fields['password'], $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Ensure admin has an email to receive the OTP
        if (!$admin->email) {
            return response()->json([
                'message' => 'No email address associated with this admin account.'
            ], 422);
        }

        // Dispatch login OTP email using OtpUtil
        OtpUtil::generateAndSend($admin->email);

        return response()->json([
            'message'      => 'Password verified. OTP code sent to your email.',
            'requires_otp' => true,
            'email'        => $admin->email,
        ]);
    }

    /**
     * Step 2 (Login): Verify Login OTP code and issue Sanctuam Bearer token.
     */
    public function completeLogin(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|numeric',
        ]);

        // Verify OTP code against cache
        $isValid = OtpUtil::verify($fields['email'], $fields['otp']);

        if (!$isValid) {
            return response()->json([
                'message' => 'Invalid or expired OTP code.'
            ], 422);
        }

        // Fetch admin record to issue auth token
        $admin = Admin::where('email', $fields['email'])->first();

        if (!$admin) {
            return response()->json(['message' => 'Admin account not found.'], 444);
        }

        $token = $admin->createToken('admin-token', ['role:admin'])->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'admin'   => $admin,
            'token'   => $token,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}