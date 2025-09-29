<?php

namespace App\Http\Controllers;

use App\Models\Deporte;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DeporteController extends Controller
{
    /**
     * Mostrar todos los deportes
     */
    public function index(): JsonResponse
    {
        $deportes = Deporte::all();
        
        return response()->json([
            'success' => true,
            'data' => $deportes
        ]);
    }

    /**
     * Crear un nuevo deporte
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255|unique:deportes,nombre'
            ], [
                'nombre.required' => 'El nombre del deporte es requerido',
                'nombre.string' => 'El nombre debe ser una cadena de texto',
                'nombre.max' => 'El nombre no puede exceder 255 caracteres',
                'nombre.unique' => 'Este deporte ya existe en la base de datos'
            ]);

            $deporte = Deporte::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Deporte creado exitosamente',
                'data' => $deporte
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Mostrar un deporte específico
     */
    public function show(string $id): JsonResponse
    {
        $deporte = Deporte::find($id);

        if (!$deporte) {
            return response()->json([
                'success' => false,
                'message' => 'Deporte no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $deporte
        ]);
    }

    /**
     * Actualizar un deporte
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $deporte = Deporte::find($id);

        if (!$deporte) {
            return response()->json([
                'success' => false,
                'message' => 'Deporte no encontrado'
            ], 404);
        }

        try {
            $validatedData = $request->validate([
                'nombre' => 'required|string|max:255|unique:deportes,nombre,' . $id
            ], [
                'nombre.required' => 'El nombre del deporte es requerido',
                'nombre.string' => 'El nombre debe ser una cadena de texto',
                'nombre.max' => 'El nombre no puede exceder 255 caracteres',
                'nombre.unique' => 'Este deporte ya existe en la base de datos'
            ]);

            $deporte->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Deporte actualizado exitosamente',
                'data' => $deporte
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Eliminar un deporte
     */
    public function destroy(string $id): JsonResponse
    {
        $deporte = Deporte::find($id);

        if (!$deporte) {
            return response()->json([
                'success' => false,
                'message' => 'Deporte no encontrado'
            ], 404);
        }

        $deporte->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deporte eliminado exitosamente'
        ]);
    }
}
