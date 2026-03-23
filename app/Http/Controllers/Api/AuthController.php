<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\JobSeeker;
use App\Models\Recruiter;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Auth;

class AuthController extends Controller
{

    public function sendOtp(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'identifier' => 'required'
        ]);

        if ($validator->fails()) {
            return errorResponse('Validation failed', 422, $validator->errors());
        }
        $identifier = $request->identifier;
        // generate OTP
        // $otp = rand(1000, 9999);
        $otp = "1234";
        Otp::create([
            'identifier' => $identifier,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(5)
        ]);
        // check if email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            \Log::info("OTP for $identifier is $otp");
        } else {
            \Log::info("OTP for $identifier is $otp");
        }
        return successResponse('OTP sent successfully');
    }

    public function verifyOtp(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'identifier' => 'required',
            'otp' => 'required|digits:4'
        ]);

        if ($validator->fails()) {
            return errorResponse('Validation failed', 422, $validator->errors());
        }

        $identifier = $request->identifier;
        $otp = $request->otp;

        // Get latest OTP
        $otpRecord = Otp::where('identifier', $identifier)
            ->latest()
            ->first();

        if (!$otpRecord) {
            return errorResponse('OTP not found');
        }

        if ($otpRecord->expires_at < now()) {
            return errorResponse('OTP expired');
        }

        if ($otpRecord->otp != $otp) {
            return errorResponse('Invalid OTP');
        }

        // Create or update user
        $user = User::firstOrNew(['mobile' => $identifier]);
        $user->is_active = true;
        $user->save();
        // (Optional) Remove old tokens → cleaner login sessions
        $user->tokens()->delete();

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;
        // Delete OTP after successful verification
        $otpRecord->delete();
        $userDetails = User::where('id', $user->id)->first();

        return successResponse('OTP verified successfully', [
            'user_details' => $userDetails,
            'token' => $token
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = Auth::user();

        /*
    |--------------------------------------------------------------------------
    | STEP 1: Basic Details
    |--------------------------------------------------------------------------
    */
        if ($request->hasAny(['username', 'password', 'email'])) {

            $request->validate([
                'username' => 'required|string|max:255',
                'password' => 'required|min:6',
                'email' => 'nullable|email|unique:users,email,' . $user->id
            ]);

            $user->username = $request->username;
            $user->password = bcrypt($request->password);
            $user->email = $request->email ?? $user->email;

            $user->basic_details = true;
            $user->save();

            return successResponse('Basic details updated', [
                'basic_details' => true
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | STEP 2: Role Selection
    |--------------------------------------------------------------------------
    */
        if ($request->has('role_id')) {

            $request->validate([
                'role_id' => 'required|in:2,3'
            ]);

            $user->role_id = $request->role_id;
            $user->role_verification = true;
            $user->save();

            return successResponse('Role selected successfully', [
                'role_verification' => true
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | STEP 3: Role-wise Profile Details
    |--------------------------------------------------------------------------
    */
        // Recruiter
        if ($user->role_id == 2) {

            $request->validate([
                'full_name' => 'required|string|max:255'
            ]);

            Recruiter::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $request->full_name
                ]
            );

            $user->profile_details = true;
            $user->save();

            return successResponse('Recruiter profile updated', [
                'profile_details' => true
            ]);
        }

        // Job Seeker
        if ($user->role_id == 3) {

            $request->validate([
                'full_name' => 'required|string'
            ]);

            JobSeeker::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'full_name' => $request->full_name
                ]
            );

            $user->profile_details = true;
            $user->save();

            return successResponse('Job seeker profile updated', [
                'profile_details' => true
            ]);
        }

        return errorResponse('Invalid request', 400);
    }


    public function userDetails(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return errorResponse('Unauthorized', 401);
        }

        // Load relations based on role
        if ($user->role_id == 2) {
            // Recruiter → can have both
            $user->load(['role', 'recruiter', 'jobSeeker',]);
        } elseif ($user->role_id == 3) {
            // Job Seeker → only job seeker
            $user->load(['role', 'jobSeeker']);
        }

        return successResponse('User details fetched successfully', [
            'user' => array_merge([
                'id' => $user->id,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'username' => $user->username,
                'role_id' => $user->role_id,

                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name
                ] : null,

                'mobile_verified' => (bool) $user->mobile_verified,
                'email_verified' => (bool) $user->email_verified,
                'is_active' => (bool) $user->is_active,

                'is_basic_completed' => (bool) $user->is_basic_completed,
                'is_role_selected' => (bool) $user->is_role_selected,
                'is_profile_completed' => (bool) $user->is_profile_completed,

                'job_seeker' => $user->jobSeeker ?? null,

            ], $user->role_id == 2 ? [
                'recruiter' => $user->recruiter
            ] : [])
        ]);
    }


    public function login(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'login' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return errorResponse('Validation failed', 422, $validator->errors());
        }

        $user = User::where('email', $request->login)
            ->orWhere('mobile', $request->login)
            ->orWhere('username', $request->login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return errorResponse('Invalid credentials');
        }

        if (!$user->is_active) {
            return errorResponse('User not active');
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load('role:id,name');
        return successResponse('Login Successful', [
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {

        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logged out'
        ]);
    }
}
