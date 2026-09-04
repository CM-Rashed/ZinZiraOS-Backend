<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail; // Reuse the exact same Mailable as Admin/Staff

class AuthController extends Controller
{
    /**
     * Step 1 (Register): Request registration OTP code
     */
    public function requestRegistrationOtp(Request $request)
    {
        $fields = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Generate a 6-digit numerical OTP code
        $otp = (string) random_int(100000, 999999);

        // Store registration payload and OTP in cache for 10 minutes
        Cache::put('reg_otp_' . $fields['email'], [
            'otp'      => $otp,
            'name'     => $fields['name'],
            'password' => $fields['password'],
        ], now()->addMinutes(10));

        // Dispatch OTP using shared OtpMail
        try {
            Mail::to($fields['email'])->send(new OtpMail($otp));
        } catch (\Exception $e) {
            // Log or handle mail exception
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Verification OTP sent to your email.',
        ], 200);
    }

    /**
     * Step 2 (Register): Verify OTP and create user account
     */
    public function completeRegistration(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string|email',
            'otp'   => 'required|string|size:6',
        ]);

        $cachedData = Cache::get('reg_otp_' . $fields['email']);

        if (!$cachedData || $cachedData['otp'] !== $fields['otp']) {
            return response()->json([
                'message' => 'Invalid or expired OTP verification code.'
            ], 422);
        }

        if (User::where('email', $fields['email'])->exists()) {
            return response()->json([
                'message' => 'User account already exists.'
            ], 409);
        }

        // Create user
        $user = User::create([
            'name'     => $cachedData['name'],
            'email'    => $fields['email'],
            'password' => Hash::make($cachedData['password']),
        ]);

        // Clear used OTP
        Cache::forget('reg_otp_' . $fields['email']);

        // Generate token
        $token = $user->createToken('user-token', ['role:user'])->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'user'    => $user,
            'token'   => $token,
            'message' => 'Account registered successfully.'
        ], 201);
    }

    /**
     * Step 1 (Login): Validate credentials & send login 2FA OTP
     */
    public function login(Request $request)
    {
        $fields = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(['message' => 'Invalid email or password.'], 401);
        }

        // Generate 6-digit OTP code
        $otp = (string) random_int(100000, 999999);

        // Store login OTP in cache for 10 minutes
        Cache::put('login_otp_' . $user->email, $otp, now()->addMinutes(10));

        // Dispatch OTP using shared OtpMail
        try {
            Mail::to($user->email)->send(new OtpMail($otp));
        } catch (\Exception $e) {
            // Log or handle mail exception
        }

        return response()->json([
            'requires_otp' => true,
            'message'      => 'A 6-digit login verification code was sent to your email.'
        ], 200);
    }

    /**
     * Step 2 (Login): Verify login OTP and dispatch auth token
     */
    public function completeLogin(Request $request)
    {
        $fields = $request->validate([
            'email' => 'required|string|email',
            'otp'   => 'required|string|size:6',
        ]);

        $user = User::where('email', $fields['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $cachedOtp = Cache::get('login_otp_' . $user->email);

        if (!$cachedOtp || $cachedOtp !== $fields['otp']) {
            return response()->json([
                'message' => 'Invalid or expired security code.'
            ], 422);
        }

        // Clear used OTP
        Cache::forget('login_otp_' . $user->email);

        // Generate token
        $token = $user->createToken('user-token', ['role:user'])->plainTextToken;

        return response()->json([
            'status' => 'success',
            'user'   => $user,
            'token'  => $token,
        ], 200);
    }

    /**
     * Logout action
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}