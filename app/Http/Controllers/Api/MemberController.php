<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\RankingHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function index(): JsonResponse
    {
        $members = Member::with('club')->orderBy('name')->get();
    
        // Agregar el último cambio de ranking
        $members->each(function ($member) {
            $latestChange = RankingHistory::forMember($member->id)
                ->latest()
                ->first();
            
            $member->latest_change = $latestChange ? $latestChange->change : null;
        });
        
        return response()->json($members);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'ranking' => 'nullable|integer',
                'age' => 'nullable|integer',
                'cedula' => 'nullable|string|max:255',
                'fecha_nacimiento' => 'nullable|date',
                'genero' => 'nullable|in:Masculino,Femenino,Otro',
                'pais' => 'nullable|string|max:255',
                'provincia' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:255',
                'celular' => 'nullable|string|max:20',
                'club_id' => 'required|exists:clubs,id',
                'drive_marca' => 'nullable|string|max:255',
                'drive_modelo' => 'nullable|string|max:255',
                'drive_tipo' => 'nullable|in:Antitopsping,Liso,Pupo Corto,Pupo Largo,Todos',
                'back_marca' => 'nullable|string|max:255',
                'back_modelo' => 'nullable|string|max:255',
                'back_tipo' => 'nullable|in:Antitopsping,Liso,Pupo Corto,Pupo Largo,Todos',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
            ]);

            $member = null;
            DB::transaction(function () use ($validatedData, &$member) {
                // 1. Crear el usuario
                $user = User::create([
                    'email' => $validatedData['email'],
                    'password' => Hash::make($validatedData['password']),
                    'role' => 'miembro',
                    'is_active' => true,
                ]);

                // 2. Preparar datos del miembro
                $memberData = collect($validatedData)->except(['email', 'password'])->toArray();
                $memberData['user_id'] = $user->id;

                // 3. Crear el miembro con todos los datos
                $member = Member::create($memberData);
            });

            return response()->json($member->load('club'), 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function show(Member $member): JsonResponse
    {
        return response()->json($member->load('club'));
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'ranking' => 'nullable|integer',
            'age' => 'nullable|integer',
            'cedula' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:Masculino,Femenino,Otro',
            'pais' => 'nullable|string|max:255',
            'provincia' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:users,email,' . $member->user_id,
            'club_id' => 'nullable|exists:clubs,id',
            'drive_marca' => 'nullable|string|max:255',
            'drive_modelo' => 'nullable|string|max:255',
            'drive_tipo' => 'nullable|in:Antitopsping,Liso,Pupo Corto,Pupo Largo,Todos',
            'back_marca' => 'nullable|string|max:255',
            'back_modelo' => 'nullable|string|max:255',
            'back_tipo' => 'nullable|in:Antitopsping,Liso,Pupo Corto,Pupo Largo,Todos',
            'password' => 'nullable|string|min:6'
        ]);

        DB::transaction(function () use ($member, $validatedData, $request) {
            $member->update(collect($validatedData)->except(['email', 'password'])->toArray());
            
            if ($member->user) {
                if ($request->filled('email')) {
                    $member->user->email = $validatedData['email'];
                }
                if ($request->filled('password')) {
                    $member->user->password = Hash::make($validatedData['password']);
                }
                $member->user->save();
            }
        });


        return response()->json($member->load('club'));
    }

    public function destroy(Member $member): JsonResponse
    {
        DB::transaction(function() use ($member) {
            if ($member->user) {
                $member->user->delete();
            }
            $member->delete();
        });
        
        return response()->json(['message' => 'Member eliminado correctamente']);
    }
}
