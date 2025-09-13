<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    public function __construct()
    {
        
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            if ($request->user() && $request->user()->isAdmin()) {
                return $next($request);
            }
            return response()->json(['message' => 'Unauthorized'], 403);
        })->except(['index', 'show']); 

     
    }

    public function pendingRequests()
    {
        
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pendingClients = Client::with('user')
                                ->where('status', 'pending')
                                ->get();

        return response()->json($pendingClients);
    }

    public function approveClient(Client $client)
    {
        
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($client->status !== 'pending') {
            return response()->json(['message' => 'Client is not in pending status.'], 400);
        }

        $client->status = 'approved';
        $client->save();

        return response()->json(['message' => 'Client approved successfully.', 'client' => $client]);
    }

    public function rejectClient(Client $client)
    {
        
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($client->status !== 'pending') {
            return response()->json(['message' => 'Client is not in pending status.'], 400);
        }

        $client->status = 'rejected';
        $client->save();

    

        return response()->json(['message' => 'Client rejected successfully.', 'client' => $client]);
    }

    public function indexPlayers()
    {
        
        return app(PlayerController::class)->index(); 
    }

    public function storePlayer(Request $request)
    {
        
        return app(PlayerController::class)->store($request); // Reutiliza el método store del PlayerController
    }

    
}
