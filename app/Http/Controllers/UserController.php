<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    
    public function store(Request $request){
        
        $request->validate([
            'name' => 'required|string|max:255',
            'account_type' => 'required|in:Individual,Business',
        ]);

        // Create a new user
        $user = User::create([
            'name' => $request->input('name'),
            'account_type' => $request->input('account_type'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
        ]);

        // Generate a JWT token for the user
        $token = JWTAuth::fromUser($user);

        return response()->json(['message' => 'User created successfully', 'user' => $user, 'token' => $token], 201);

    }

}
