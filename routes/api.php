<?php


use App\Http\Controllers\TournamentController;
use App\Http\Controllers\Api\BracketController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\ClubController;
use App\Http\Controllers\Api\LigaController;
use App\Http\Controllers\Api\RankingHistoryController;
use App\Http\Controllers\DeporteController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\Api\SuperadminController;

Route::apiResource('categorias', CategoriaController::class);

Route::get('categorias/buscar/codigo', [CategoriaController::class, 'findByCode']);

Route::apiResource('deportes', DeporteController::class);

Route::apiResource('ligas', LigaController::class);

Route::apiResource('clubs', ClubController::class);

Route::post('/tournaments', [TournamentController::class, 'store']);

Route::get('/tournaments', [TournamentController::class, 'index']);

Route::delete('/tournaments/{tournament}', [TournamentController::class, 'destroy']);

Route::post('/tournaments/{tournament}/register', [TournamentController::class, 'registerMember']);

Route::post('/tournaments/{tournament}/generate-bracket', [BracketController::class, 'generateBracket']);


Route::get('/tournaments/{tournament}/bracket', [BracketController::class, 'getBracket']);

Route::get('/tournaments/{tournament}/standings', [BracketController::class, 'getStandings']); 

Route::apiResource('members', MemberController::class);

Route::get('/tournaments/{tournament}/members', [TournamentController::class, 'showMembers']);

// Ranking History - ✅ NUEVAS RUTAS
Route::prefix('members/{member}')->group(function () {
    // Historial de ranking (últimos 12 meses agrupado para gráfico)
    Route::get('ranking-history', [RankingHistoryController::class, 'show']);
    
    // Historial detallado (todos los cambios con paginación)
    Route::get('ranking-history/detailed', [RankingHistoryController::class, 'detailed']);
    
    // Estadísticas de ranking
    Route::get('ranking-stats', [RankingHistoryController::class, 'stats']);
});

Route::put('/games/{id}', [GameController::class, 'update']);

// --- Superadmin Routes ---
Route::prefix('superadmin')->group(function () {
    Route::get('/users', [SuperadminController::class, 'listUsers']);
    Route::post('/users/status', [SuperadminController::class, 'updateUserStatus']);
});


