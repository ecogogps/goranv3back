<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        $members = Member::with('club')->orderBy('name')->get();
        return response()->json($members);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ranking' => 'nullable|integer',
            'age' => 'nullable|integer',
            'cedula' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:Masculino,Femenino,Otro',
            'pais' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'club_id' => 'nullable|exists:clubs,id',
            'drive_marca' => 'nullable|string|max:255',
            'drive_modelo' => 'nullable|string|max:255',
            'drive_tipo' => 'nullable|in:Antitopsping,Liso,Pupo Corto,Pupo Largo,Todos',
            'back_marca' => 'nullable|string|max:255',
            'back_modelo' => 'nullable|string|max:255',
            'back_tipo' => 'nullable|in:Antitopsping,Liso,Pupo Corto,Pupo Largo,Todos'
        ]);

        $member = Member::create($request->all());
        return response()->json($member->load('club'), 201);
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json($member->load('club'));
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ranking' => 'nullable|integer',
            'age' => 'nullable|integer',
            'cedula' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:Masculino,Femenino,Otro',
            'pais' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'club_id' => 'nullable|exists:clubs,id',
            'drive_marca' => 'nullable|string|max:255',
            'drive_modelo' => 'nullable|string|max:255',
            'drive_tipo' => 'nullable|in:Antitopsping,Liso,Pupo Corto,Pupo Largo,Todos',
            'back_marca' => 'nullable|string|max:255',
            'back_modelo' => 'nullable|string|max:255',
            'back_tipo' => 'nullable|in:Antitopsping,Liso,Pupo Corto,Pupo Largo,Todos'
        ]);

        $member->update($request->all());
        return response()->json($member->load('club'));
    }

    public function destroy(Member $member): JsonResponse
    {
        $member->delete();
        return response()->json(['message' => 'Member eliminado correctamente']);
    }
}
