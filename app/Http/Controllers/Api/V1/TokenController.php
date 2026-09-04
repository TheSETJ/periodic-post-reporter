<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;

class TokenController extends Controller
{
    public function store(LoginRequest $request)
    {
        $user = User::where('username', $request->username)
            ->orWhere('email', $request->email)
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $user->createToken('login');

        return response()->json([
            'token' => $token->plainTextToken,
        ], Response::HTTP_CREATED);
    }
}
