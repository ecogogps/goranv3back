<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ClubController extends Controller
{
    public function index(): JsonResponse
    {
        $clubs = Club::orderBy('nombre')->get();
        
        $clubs->transform(function ($club) {
            if ($club->imagen) {
                // Corrected URL generation to point directly to the public storage path
                $club->imagen_url = 'https://00591b4e804e.ngrok-free.app/storage/' . $club->imagen;
            } else {
                $club->imagen_url = null;
            }
            return $club;
        });
        
        return response()->json($clubs);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $club = new Club();
        $club->nombre = $request->nombre;

        if ($request->hasFile('imagen')) {
            $imagePath = $request->file('imagen')->store('clubs', 'public');
            $club->imagen = $imagePath;
        }

        $club->save();

        // Agregar URL de imagen para la respuesta
        if ($club->imagen) {
            // Corrected URL generation
            $club->imagen_url = 'https://00591b4e804e.ngrok-free.app/storage/' . $club->imagen;
        } else {
            $club->imagen_url = null;
        }

        return response()->json($club, 201);
    }

    public function show(Club $club): JsonResponse
    {
        $club->load('members');
        
        // Agregar URL de imagen
        if ($club->imagen) {
            // Corrected URL generation
            $club->imagen_url = 'https://00591b4e804e.ngrok-free.app/storage/' . $club->imagen;
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
            $club->imagen_url = 'https://00591b4e804e.ngrok-free.app/storage/' . $club->imagen;
        } else {
            $club->imagen_url = null;
        }

        return response()->json($club);
    }

    public function destroy(Club $club): JsonResponse
    {
        // Eliminar imagen si existe
        if ($club->imagen && Storage::disk('public')->exists($club->imagen)) {
            Storage::disk('public')->delete($club->imagen);
        }

        $club->delete();

        return response()->json(['message' => 'Club eliminado correctamente']);
    }
}
