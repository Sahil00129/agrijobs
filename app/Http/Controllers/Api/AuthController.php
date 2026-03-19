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
            $otp = rand(1000, 9999);
            Otp::create([
                'identifier' => $identifier,
                'otp' => $otp,
                'expires_at' => now()->addMinutes(5)
            ]);
            // check if email
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                // send email (basic)
                Mail::raw("Your OTP is: $otp", function ($message) use ($identifier) {
                    $message->to($identifier)
                            ->subject('Your OTP Code');
                });
            } else {
                \Log::info("OTP for $identifier is $otp");
            }
            return response()->json([
                'status' => 'success',
                'message' => 'OTP sent successfully',
                'statuscode' => '200',
                'data' => []

            ]);
        }

    // public function register(Request $request)
    // {

    //    $validator = Validator::make($request->all(),[
    //     'email'=>'nullable|email|unique:users',
    //     'mobile'=>'nullable|unique:users',
    //     'password'=>'required|min:6'
    // ]);

    // if($validator->fails()){
    //     return response()->json([
    //         'status'=>false,
    //         'errors'=>$validator->errors()
    //     ],422);
    // }

    //     $user = User::create([
    //         'email'=>$request->email,
    //         'mobile'=>$request->mobile,
    //         'role_id'=>3,
    //         'password'=>Hash::make($request->password),
    //         'is_verified'=>1
    //     ]);

    //     $token = $user->createToken('auth_token')->plainTextToken;
    //     // Load role relationship
    //     $user->load('role:id,name');

    //     return response()->json([
    //         'accessToken'=>$token,
    //         'user'=>$user,
    //     ]);
    // }

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