<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{ 

        public function sendOtp(Request $request)
            {
                $request->validate([
                    'identifier' => 'required' // email OR mobile
                ]);
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
                    // send email (basic)
                    // Mail::raw("Your OTP is: $otp", function ($message) use ($identifier) {
                    //     $message->to($identifier)
                    //             ->subject('Your OTP Code');
                    // });
                    \Log::info("OTP for $identifier is $otp");
                } else {
                    \Log::info("OTP for $identifier is $otp");
                }
                 return successResponse('OTP sent successfully');
            }

       public function verifyOtp(Request $request)
                {
                    $request->validate([
                        'identifier' => 'required',
                        'otp' => 'required|digits:4'
                    ]);

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
                    $user->mobile_verified = true;
                    $user->is_active = true;
                    $user->save();
                    // (Optional) Remove old tokens → cleaner login sessions
                    $user->tokens()->delete();

                    // Generate Sanctum token
                    $token = $user->createToken('auth_token')->plainTextToken;
                    // Delete OTP after successful verification
                    $otpRecord->delete();

                    return successResponse('OTP verified successfully', [
                        'user' => $user,
                        'token' => $token
                    ]);
                }


    public function login(Request $request)
    {

        $request->validate([
            'login'=>'required',
            'password'=>'required'
        ]);

        $user = User::where('email',$request->login)
                ->orWhere('mobile',$request->login)
                ->first();

        if(!$user || !Hash::check($request->password,$user->password)){
            return response()->json([
                'message'=>'Invalid credentials'
            ],401);
        }

        if(!$user->is_verified){
            return response()->json([
                'message'=>'User not verified'
            ],403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        $user->load('role:id,name');
        return response()->json([
            'token'=>$token,
            'user'=>$user
        ]);
    }

    public function logout(Request $request)
    {

        $request->user()->tokens()->delete();

        return response()->json([
            'message'=>'Logged out'
        ]);
    }

}