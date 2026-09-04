<?php

namespace App\Utils;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class OtpUtil
{
    /**
     * Generate, store, and send an OTP code for general purpose / Login.
     */
    public static function generateAndSend(string $email, int $ttlMinutes = 10): bool
    {
        // 1. Generate 6-digit numeric code
        $otp = random_int(100000, 999999);

        // 2. Store OTP code in Cache
        Cache::put('otp_' . $email, $otp, now()->addMinutes($ttlMinutes));

        // 3. Dispatch Email
        Mail::to($email)->send(new OtpMail($otp));

        return true;
    }

    /**
     * Verify a standard OTP code (used during Login).
     */
    public static function verify(string $email, string $otp): bool
    {
        $cachedOtp = Cache::get('otp_' . $email);

        if (!$cachedOtp || $cachedOtp != $otp) {
            return false;
        }

        // Clear OTP after successful verification
        Cache::forget('otp_' . $email);

        return true;
    }

    /**
     * Stash registration payload and send OTP code.
     */
    public static function sendRegistrationOtp(string $email, array $pendingData, int $ttlMinutes = 10): void
    {
        $otp = random_int(100000, 999999);

        // Cache registration details alongside OTP code
        Cache::put('otp_reg_' . $email, [
            'otp'  => $otp,
            'data' => $pendingData,
        ], now()->addMinutes($ttlMinutes));

        // Send OTP via email
        Mail::to($email)->send(new OtpMail($otp));
    }

    /**
     * Verify registration OTP code and retrieve cached user data.
     */
    public static function verifyAndRetrieveData(string $email, string $otp): ?array
    {
        $cached = Cache::get('otp_reg_' . $email);

        if (!$cached || $cached['otp'] != $otp) {
            return null;
        }

        // Clear pending registration data after match
        Cache::forget('otp_reg_' . $email);

        return $cached['data'];
    }
}