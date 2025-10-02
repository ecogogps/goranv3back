<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class ClubController extends Controller
{
    public function index(): JsonResponse
    {
        $clubs = Club::with('liga')->withCount('members')->orderBy('nombre')->get();
        
        $clubs->transform(function ($club) {
            if ($club->imagen) {
                // Corrected URL generation to point directly to the public storage path
                $club->imagen_url = 'https://trollopy-ephraim-hypoxanthic.ngrok-free.dev/storage/' . $club->imagen;
            } else {
                $club->imagen_url = null;
            }
            return $club;
        });
        
        return response()->json($clubs);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255',
                'imagen' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'ruc' => 'required|string|max:20',
                'liga_id' => 'required|exists:ligas,id',
                'pais' => 'required|string|max:100',
                'provincia' => 'required|string|max:100',
                'ciudad' => 'required|string|max:100',
                'direccion' => 'required|string|max:255',
                'celular' => 'required|string|max:20',
                'google_maps_url' => 'required|url|max:500',
                'representante_nombre' => 'required|string|max:255',
                'representante_telefono' => 'required|string|max:20',
                'representante_email' => 'required|email|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                // Optional admin fields
                'admin1_nombre' => 'nullable|string|max:255',
                'admin1_telefono' => 'nullable|string|max:20',
                'admin1_email' => 'nullable|email|max:255',
                'admin2_nombre' => 'nullable|string|max:255',
                'admin2_telefono' => 'nullable|string|max:20',
                'admin2_email' => 'nullable|email|max:255',
                'admin3_nombre' => 'nullable|string|max:255',
                'admin3_telefono' => 'nullable|string|max:20',
                'admin3_email' => 'nullable|email|max:255',
            ]);

            $club = null;
            DB::transaction(function () use ($request, $validatedData, &$club) {
                // 1. Crear el usuario
                $user = User::create([
                    'email' => $validatedData['email'],
                    'password' => Hash::make($validatedData['password']),
                    'role' => 'club',
                    'is_active' => true, // Clubs are active by default
                ]);

                // 2. Preparar los datos del club
                $clubData = $request->except(['email', 'password', 'imagen']);
                $clubData['user_id'] = $user->id;

                // 3. Guardar la imagen si existe
                if ($request->hasFile('imagen')) {
                    $imagePath = $request->file('imagen')->store('clubs', 'public');
                    $clubData['imagen'] = $imagePath;
                }

                // 4. Crear el club
                $club = Club::create($clubData);
            });
            
            if ($club && $club->imagen) {
                 $club->imagen_url = 'https://trollopy-ephraim-hypoxanthic.ngrok-free.dev/storage/' . $club->imagen;
            }

            return response()->json([
                'success' => true,
                'message' => 'Club creado exitosamente.',
                'data' => $club
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        }
    }


    public function show(Club $club): JsonResponse
    {
        $club->load('members', 'liga');
        
        // Agregar URL de imagen
        if ($club->imagen) {
            // Corrected URL generation
            $club->imagen_url = 'https://trollopy-ephraim-hypoxanthic.ngrok-free.dev/storage/' . $club->imagen;
        } else {
            $club->imagen_url = null;
        }
        
        return response()->json($club);
    }

    public function update(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $club->nombre = $request->nombre;

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($club->imagen && Storage::disk('public')->exists($club->imagen)) {
                Storage::disk('public')->delete($club->imagen);
            }
            
            $imagePath = $request->file('imagen')->store('clubs', 'public');
            $club->imagen = $imagePath;
        }

        $club->save();

        // Agregar URL de imagen para la respuesta
        if ($club->imagen) {
            // Corrected URL generation
            $club->imagen_url = 'https://trollopy-ephraim-hypoxanthic.ngrok-free.dev/storage/' . $club->imagen;
        } else {
            $club->imagen_url = null;
        }

        return response()->json($club);
    }

    public function destroy(Club $club): JsonResponse
    {
        // Iniciar una transacción
        DB::transaction(function () use ($club) {
            // Eliminar imagen si existe
            if ($club->imagen && Storage::disk('public')->exists($club->imagen)) {
                Storage::disk('public')->delete($club->imagen);
            }

            // Eliminar el usuario asociado
            if ($club->user) {
                $club->user->delete();
            }

            // Eliminar el club (esto debería eliminar en cascada miembros si se configura así)
            $club->delete();
        });


        return response()->json(['message' => 'Club eliminado correctamente']);
    }
}
