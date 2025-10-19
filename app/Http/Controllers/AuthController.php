<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $r)
    {
        $r->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        $user = User::create([
            'name' => $r->name,
            'email' => $r->email,
            'password' => bcrypt($r->password),
        ]);

        $token = $user->createToken('app')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

public function login(Request $r)
{
    $r->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('email', $r->email)->first();
    if (!$user || !\Illuminate\Support\Facades\Hash::check($r->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('app')->plainTextToken;
    return response()->json(['token' => $token, 'user' => $user]);
}

}
