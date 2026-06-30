<?php

namespace App\Http\Controllers\API;

use Session;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;

class UserController extends Controller
{
    /**
     * Register
     */
    public function register(Request $request)
    {
        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            $success = true;
            $message = 'User register successfully';
        } catch (\Illuminate\Database\QueryException $ex) {
            $success = false;
            $message = $ex->getMessage();
        }

        // response
        $response = [
            'success' => $success,
            'message' => $message,
        ];
        event(new Registered($user));


        return response()->json($response);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (Auth::attempt($credentials)) {
            $success = true;
            $message = 'User login successfully';
        } else {
            $success = false;
            $message = 'Unauthorised';
        }

        // response
        $response = [
            'success' => $success,
            'message' => $message,
        ];
        return response()->json($response);
    }

    /**
     * Logout
     */
    public function logout()
    {
        try {
            Session::flush();
            $success = true;
            $message = 'Successfully logged out';
        } catch (\Illuminate\Database\QueryException $ex) {
            $success = false;
            $message = $ex->getMessage();
        }

        // response
        $response = [
            'success' => $success,
            'message' => $message,
        ];
        return response()->json($response);
    }
    public function checkUser(Request $request){

        if(User::where('social_id',$request->social_id)
                ->where('social',$request->social)
                ->exists()){
                    return "true";
                }
                return "false";
    }
    public function getUser(Request $request){

        $user = User::where('social_id',$request->social_id)
                ->where('social',$request->social)
                ->first();
        return response()->json($user);    
    }
    public function saveUser(Request $request){
        $user = new User();
        $user->name = $request->name;
        $user->social_id = $request->social_id;
        $user->social = $request->social;
        $user->email = $request->email;
        $user->password = Hash::make(uniqid());
        $user->status = 1;
        $user->paid = 1;
        $user->save();

        return response()->json($user);
    }

    public function saveFcmToken(Request $request){
        $user = User::find($request->user_id);
        if($user){
            $user->fcm_token = $request->fcm_token;
            $user->save();
            return response()->json(['success' => true]);
        }
        return response()->json(['success' => false, 'message' => 'User not found'], 404);
    }
}