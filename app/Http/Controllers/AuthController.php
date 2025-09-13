<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'nombre_empresa' => 'required|string|max:255',
            'ruc' => 'required|string|max:20|unique:clients',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'client',
        ]);

        $client = Client::create([
            'nombre_empresa' => $request->nombre_empresa,
            'ruc' => $request->ruc,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $user->client_id = $client->id;
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Awaiting administrator approval.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'client_status' => $client->status,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        
        if ($user->isClient()) {
            
            if (!$user->relationLoaded('client')) {
                $user->load('client');
            }

           
            if (!$user->client || $user->client->status === 'pending') {
                throw ValidationException::withMessages([
                    'email' => ['Your account is pending approval by the administrator.'],
                ]);
            }
            if ($user->client->status === 'rejected') {
                throw ValidationException::withMessages([
                    'email' => ['Your account has been rejected by the administrator.'],
                ]);
            }
        }
        

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('client'), // Cargar la relación del cliente para la respuesta
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }


    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin',
        ]);

        $token = $user->createToken('admin_token', ['admin'])->plainTextToken;

        return response()->json([
            'message' => 'Admin user created successfully.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }
}
