<?php


use App\Http\Controllers\TournamentController;
use App\Http\Controllers\Api\BracketController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\ClubController;

Route::apiResource('clubs', ClubController::class);

Route::post('/tournaments', [TournamentController::class, 'store']);

Route::get('/tournaments', [TournamentController::class, 'index']);


Route::post('/tournaments/{tournament}/generate-bracket', [BracketController::class, 'generateBracket']);


Route::get('/tournaments/{tournament}/bracket', [BracketController::class, 'getBracket']);

Route::get('/tournaments/{tournament}/standings', [BracketController::class, 'getStandings']); 

Route::apiResource('members', MemberController::class);

Route::get('/tournaments/{tournament}/members', [TournamentController::class, 'showMembers']);

Route::put('/games/{id}', [GameController::class, 'update']);


