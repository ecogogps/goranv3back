<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CategoriaController extends Controller
{
    /**
     * Mostrar todas las categorías (opcionalmente filtradas por liga)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Categoria::with('liga');
        
        // Filtrar por liga si se proporciona el parámetro
        if ($request->has('liga_id')) {
            $query->where('liga_id', $request->liga_id);
        }
        
        $categorias = $query->get();
        
        return response()->json([
            'success' => true,
            'data' => $categorias
        ]);
    }

    /**
     * Crear una nueva categoría
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'liga_id' => 'required|exists:ligas,id',
                'code_start' => 'required|integer|min:0',
                'code_end' => 'required|integer|min:0|gte:code_start'
            ], [
                'liga_id.required' => 'El ID de la liga es requerido',
                'liga_id.exists' => 'La liga especificada no existe',
                'code_start.required' => 'El código inicial es requerido',
                'code_start.integer' => 'El código inicial debe ser un número entero',
                'code_start.min' => 'El código inicial debe ser mayor o igual a 0',
                'code_end.required' => 'El código final es requerido',
                'code_end.integer' => 'El código final debe ser un número entero',
                'code_end.min' => 'El código final debe ser mayor o igual a 0',
                'code_end.gte' => 'El código final debe ser mayor o igual al código inicial'
            ]);

            // Validar que no haya solapamiento de rangos DENTRO DE LA MISMA LIGA
            $overlap = Categoria::where('liga_id', $validatedData['liga_id'])
                ->where(function($query) use ($validatedData) {
                    $query->whereBetween('code_start', [$validatedData['code_start'], $validatedData['code_end']])
                          ->orWhereBetween('code_end', [$validatedData['code_start'], $validatedData['code_end']])
                          ->orWhere(function($q) use ($validatedData) {
                              $q->where('code_start', '<=', $validatedData['code_start'])
                                ->where('code_end', '>=', $validatedData['code_end']);
                          });
                })->exists();

            if ($overlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rango de códigos se solapa con una categoría existente en esta liga'
                ], 422);
            }

            $categoria = Categoria::create($validatedData);
            $categoria->load('liga');

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => $categoria
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
     * Mostrar una categoría específica
     */
    public function show(string $id): JsonResponse
    {
        $categoria = Categoria::with('liga')->find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $categoria
        ]);
    }

    /**
     * Actualizar una categoría
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        try {
            $validatedData = $request->validate([
                'liga_id' => 'required|exists:ligas,id',
                'code_start' => 'required|integer|min:0',
                'code_end' => 'required|integer|min:0|gte:code_start'
            ], [
                'liga_id.required' => 'El ID de la liga es requerido',
                'liga_id.exists' => 'La liga especificada no existe',
                'code_start.required' => 'El código inicial es requerido',
                'code_start.integer' => 'El código inicial debe ser un número entero',
                'code_start.min' => 'El código inicial debe ser mayor o igual a 0',
                'code_end.required' => 'El código final es requerido',
                'code_end.integer' => 'El código final debe ser un número entero',
                'code_end.min' => 'El código final debe ser mayor o igual a 0',
                'code_end.gte' => 'El código final debe ser mayor o igual al código inicial'
            ]);

            // Validar solapamiento excluyendo la categoría actual y dentro de la misma liga
            $overlap = Categoria::where('id', '!=', $id)
                ->where('liga_id', $validatedData['liga_id'])
                ->where(function($query) use ($validatedData) {
                    $query->whereBetween('code_start', [$validatedData['code_start'], $validatedData['code_end']])
                          ->orWhereBetween('code_end', [$validatedData['code_start'], $validatedData['code_end']])
                          ->orWhere(function($q) use ($validatedData) {
                              $q->where('code_start', '<=', $validatedData['code_start'])
                                ->where('code_end', '>=', $validatedData['code_end']);
                          });
                })->exists();

            if ($overlap) {
                return response()->json([
                    'success' => false,
                    'message' => 'El rango de códigos se solapa con una categoría existente en esta liga'
                ], 422);
            }

            $categoria->update($validatedData);
            $categoria->load('liga');

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => $categoria
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
     * Eliminar una categoría
     */
    public function destroy(string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }

    /**
     * Buscar categoría por código dentro de una liga
     */
    public function findByCode(Request $request): JsonResponse
    {
        $request->validate([
            'liga_id' => 'required|exists:ligas,id',
            'code' => 'required|integer|min:0'
        ]);

        $categoria = Categoria::where('liga_id', $request->liga_id)
                              ->where('code_start', '<=', $request->code)
                              ->where('code_end', '>=', $request->code)
                              ->with('liga')
                              ->first();

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una categoría para el código proporcionado en esta liga'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $categoria
        ]);
    }
}
