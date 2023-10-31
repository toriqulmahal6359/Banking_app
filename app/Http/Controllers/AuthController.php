<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    public function __construct(){
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function login(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $token = $this->guard()->attempt($credentials);
        // Attempt to authenticate the user
        // if (Auth::attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
            
        if($token){    
            return $this->respondWithToken($token);
            //->json(['message' => 'Login successful', 'user' => $this->guard()->user()])
        } else {
            return response()->json(['message' => 'Login failed'], 401);
        }
    }

    protected function respondWithToken($token){
        return response()->json([
            "access_token" => $token,
            "token_type" => "bearer",
            "expires_in" => auth('api')->factory()->getTTL()*60
        ]);
    }

    protected function guard(){
        return Auth::guard();
    }
}

