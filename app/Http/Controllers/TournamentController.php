<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    public function index()
    {
        $tournaments = Tournament::withCount(['games', 'members'])->orderBy('created_at', 'desc')->get();
        
        // Transformar la ruta relativa de la imagen en URL completa
        $tournaments->transform(function ($tournament) {
            if ($tournament->main_image_path) {
                $tournament->main_image_url = 'https://8d4e1417523b.ngrok-free.app' . Storage::url($tournament->main_image_path);
            } else {
                $tournament->main_image_url = null;
            }
            return $tournament;
        });
        
        return response()->json($tournaments, 200); 
    }
    
    public function showMembers(Tournament $tournament)
    {
        
        $members = $tournament->members()->get();

        
        return response()->json($members);
    }

    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'tournament_code' => 'required|string|unique:tournaments,tournament_code',
            'name' => 'required|string|max:255',
            'country' => 'required|string',
            'province' => 'required|string',
            'city' => 'required|string',
            'club_name' => 'required|string',
            'address' => 'required|string',
            'date' => 'required|date',
            'time' => 'required',
            'registration_deadline' => 'required|date',
            'modality' => 'required|string',
            'match_type' => 'required|string',
            'elimination_type' => 'required|string',
            'participants_number' => 'required|integer',
            'seeding_type' => 'required|string',
            'ranking_all' => 'required|boolean',
            'ranking_from' => 'nullable|string',
            'ranking_to' => 'nullable|string',
            'age_all' => 'required|boolean',
            'age_from' => 'nullable|integer',
            'age_to' => 'nullable|integer',
            'gender' => 'required|string',
            'affects_ranking' => 'required|boolean',
            'system_invitation' => 'required|boolean',
            'resend_invitation_schedule' => 'nullable|string',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
            'prize1' => 'nullable|string',
            'prize2' => 'nullable|string',
            'prize3' => 'nullable|string',
            'prize4' => 'nullable|string',
            'prize5' => 'nullable|string',
            'contact_name' => 'required|string',
            'contact_phone' => 'required|string',
            'ball_info' => 'required|string',
            
            'advancers_per_group' => 'nullable|integer|min:1',

            // nuevos campos
            'tournament_price' => 'nullable|numeric|min:0',
            'rubber_type' => 'nullable|in:Liso,Pupo,Todos',
            'groups_number' => 'nullable|integer|min:1',
            'rounds' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        
        $imagePath = null;
        if ($request->hasFile('main_image')) {
            
            
            $imagePath = $request->file('main_image')->store('tournament_images', 'public');
        }

        
        $tournamentData = $request->except('main_image');
        $tournamentData['main_image_path'] = $imagePath;

        
        if (!isset($tournamentData['advancers_per_group']) || $tournamentData['advancers_per_group'] === null) {
            $tournamentData['advancers_per_group'] = 2;
        }

        $tournament = Tournament::create($tournamentData);

        return response()->json([
            'message' => '¡Torneo creado con éxito!',
            'data' => $tournament
        ], 201);
    }

    public function destroy(Tournament $tournament)
    {
        try {
            // Eliminar la imagen asociada si existe
            if ($tournament->main_image_path && Storage::disk('public')->exists($tournament->main_image_path)) {
                Storage::disk('public')->delete($tournament->main_image_path);
            }

            // Eliminar el torneo (esto también eliminará las relaciones si están configuradas con cascade)
            $tournament->delete();

            return response()->json([
                'message' => 'Torneo eliminado con éxito'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el torneo',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
