<?php

namespace App\Http\Controllers;

use App\Notifications\SendUserNotification;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string'
        ]);
        if (!$validator->fails()) {
            if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
                $user = Auth::user();
                Auth::login($user);
                $token = $user->createToken('MyApp')->accessToken;
                return response()->json(['message' => 'success', 'access_token' => $token, 'user' => $request->user()], 200);

            } else {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
        } else {
            return response()->json([
                'message' => 'field error',
                'data' => $validator->errors()
            ], 400);
        }

    }

    public function email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid Email',
                'data' => $validator->errors()
            ], 400);
        } else {
            if (User::where('email', $request->input('email'))->exists()) {
                $user = User::where('email', $request->input('email'))->first();
                $otp = random_int(100000, 999999);
                $user->update([
                    'otp' => $otp
                ]);
                $data = ['name' => $user->name, "otp" => $user->otp, 'email' => $user->email];
                Mail::send('emails.mail', ['data' => $data], function ($message) use ($data) {
                    $message->to($data['email'], $data['name'])
                        ->subject('Otp code reset')
                         ->from('noprex-team@gmail.com','Derrick Ngatia CEO');;
                });
                return response()->json([
                    'message' => 'Otp sent to email',
                ], 200);
            } else {
                return response()->json([
                    'message' => 'No such email in our records!!',
                ], 404);
            }
        }
    }

    public function resetPassword(Request $request)
    {
        $user = User::where('email', $request->input('email'))->first();
        if (str_is($user->otp, $request->input('otp'))) {
            $user->fill(['password' => Hash::make($request->input('password'))])->save();
            return response()->json([
                'message' => 'Password changed successfully',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Invalid otp',
            ], 404);
        }
    }
}
