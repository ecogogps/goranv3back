<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class LigaController extends Controller
{
    /**
     * Listar todas las ligas
     */
    public function index(Request $request): JsonResponse
    {
        $query = Liga::with('deporte');
        
        // Filtrar por deporte si se proporciona
        if ($request->has('deporte_id')) {
            $query->where('deporte_id', $request->deporte_id);
        }
        
        $ligas = $query->orderBy('name')->get();
        
        return response()->json([
            'success' => true,
            'data' => $ligas
        ]);
    }

    /**
     * Crear una nueva liga y su usuario asociado.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'pais' => 'nullable|string|max:255',
                'provincia' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:255',
                'celular' => 'nullable|string|max:20',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'deporte_id' => 'nullable|exists:deportes,id'
            ], [
                'name.required' => 'El nombre de la liga es requerido.',
                'email.required' => 'El correo electrónico es requerido.',
                'email.unique' => 'Este correo electrónico ya está en uso.',
                'password.required' => 'La contraseña es requerida.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            ]);

            $liga = null;
            DB::transaction(function () use ($validatedData, &$liga) {
                // 1. Crear el usuario
                $user = User::create([
                    'email' => $validatedData['email'],
                    'password' => Hash::make($validatedData['password']),
                    'role' => 'liga',
                    'is_active' => true,
                ]);

                // 2. Crear la liga y asociarla con el usuario
                $liga = Liga::create([
                    'name' => $validatedData['name'],
                    'pais' => $validatedData['pais'],
                    'provincia' => $validatedData['provincia'],
                    'ciudad' => $validatedData['ciudad'],
                    'celular' => $validatedData['celular'],
                    'deporte_id' => $validatedData['deporte_id'] ?? 2, // Default to 1 if not provided
                    'user_id' => $user->id,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Liga creada exitosamente.',
                'data' => $liga
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        }
    }


    /**
     * Mostrar una liga específica
     */
    public function show(Liga $liga): JsonResponse
    {
        $liga->load(['deporte', 'clubs.members', 'categorias', 'user']);
        
        return response()->json([
            'success' => true,
            'data' => $liga
        ]);
    }

    /**
     * Actualizar una liga
     */
    public function update(Request $request, Liga $liga): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'deporte_id' => 'required|exists:deportes,id',
            'pais' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255'
        ], [
            'name.required' => 'El nombre de la liga es requerido',
            'deporte_id.required' => 'El deporte es requerido',
            'deporte_id.exists' => 'El deporte seleccionado no existe'
        ]);

        $liga->update($validated);
        $liga->load('deporte');

        return response()->json([
            'success' => true,
            'message' => 'Liga actualizada exitosamente',
            'data' => $liga
        ]);
    }

    /**
     * Eliminar una liga
     */
    public function destroy(Liga $liga): JsonResponse
    {
        if ($liga->user) {
            $liga->user->delete();
        }
        $liga->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Liga eliminada correctamente'
        ]);
    }
}
