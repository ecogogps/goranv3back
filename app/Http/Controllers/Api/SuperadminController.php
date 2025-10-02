<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;

class SuperadminController extends Controller
{
    /**
     * List all users from the central users table.
     */
    public function listUsers(): JsonResponse
    {
        // Eager load the specific relationships based on roles
        $users = User::with(['liga', 'club', 'miembro'])
            ->where('role', '!=', 'superadmin')
            ->get();

        $allUsers = $users->map(function ($user) {
            $profile = $user->profile; // Use the accessor
            $name = 'N/A';
            if ($profile) {
                // 'nombre' for Club, 'name' for Liga and Member
                $name = $profile->name ?? $profile->nombre ?? 'N/A';
            }

            return [
                'id' => $user->id,
                'nombre' => $name,
                'correo' => $user->email,
                'rol' => ucfirst($user->role), // Capitalize role name
                'activo' => $user->is_active,
            ];
        });

        return response()->json($allUsers->sortBy('nombre')->values()->all());
    }

    /**
     * Update a user's status in the central users table.
     */
    public function updateUserStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'activo' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::find($request->input('id'));
        
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $user->is_active = $request->input('activo');
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado del usuario actualizado correctamente.',
        ]);
    }
}
