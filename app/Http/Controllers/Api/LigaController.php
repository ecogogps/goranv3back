<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Liga;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LigaController extends Controller
{
    public function index(): JsonResponse
    {
        $ligas = Liga::orderBy('name')->get();
        return response()->json($ligas);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'pais' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255'
        ]);

        $liga = Liga::create($request->all());
        return response()->json($liga, 201);
    }

    public function show(Liga $liga): JsonResponse
    {
        return response()->json($liga->load('clubs.members'));
    }

    public function update(Request $request, Liga $liga): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'pais' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255'
        ]);

        $liga->update($request->all());
        return response()->json($liga);
    }

    public function destroy(Liga $liga): JsonResponse
    {
        $liga->delete();
        return response()->json(['message' => 'Liga eliminada correctamente']);
    }
}
