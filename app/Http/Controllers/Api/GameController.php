<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Tournament;
use App\Http\Controllers\Api\BracketController; 
use App\Http\Controllers\Api\Traits\ManagesRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    use ManagesRanking;

    /**
     * Update the specified game in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'score1' => 'required|integer|min:0',
            'score2' => 'required|integer|min:0',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        return DB::transaction(function () use ($request, $id) {
            $game = Game::findOrFail($id);
            $tournament = $game->tournament;
    
            if (!$game->member1_id || !$game->member2_id) {
                return response()->json(['message' => 'No se puede registrar un resultado para un partido sin ambos jugadores definidos.'], 400);
            }
    
            $score1 = (int)$request->input('score1');
            $score2 = (int)$request->input('score2');
    
            
            $winnerId = null;
            if ($score1 > $score2) {
                $winnerId = $game->member1_id;
            } elseif ($score2 > $score1) {
                $winnerId = $game->member2_id;
            } else { 
                if ($tournament->elimination_type !== 'round_robin') {
                    
                    return response()->json(['message' => 'Los marcadores no pueden ser iguales. No se permiten empates en fases de eliminación.'], 400);
                }
                
            }
    
            $game->score1 = $score1;
            $game->score2 = $score2;
            $game->winner_id = $winnerId;
            $game->status = 'completed';
            $game->save();

            
            $rankingUpdate = null;
            try {
                $rankingUpdate = $this->processGameRankingUpdate($game);
                
                if ($rankingUpdate && $rankingUpdate['success']) {
                    Log::info("Ranking actualizado exitosamente para el partido {$game->id}", $rankingUpdate);
                }
            } catch (\Exception $e) {
                
                Log::error("Error procesando actualización de ranking para partido {$game->id}", [
                    'error' => $e->getMessage(),
                    'game_id' => $game->id,
                    'tournament_id' => $game->tournament_id
                ]);
            }

            
            $isGroupStageMatch = $game->group_name && str_contains($game->group_name, 'Grupo');
    
            if ($isGroupStageMatch) {
                
                $tournament = Tournament::find($game->tournament_id);
                if ($tournament) {
                    BracketController::placeAdvancersInElimination(
                        $game->tournament_id, 
                        $game->group_name, 
                        $tournament->advancers_per_group ?? 2
                    );
                }
            } else {
                
                $advanced = $this->advanceWinnerToNextRound($game);
                
                
                if (!$advanced && $winnerId) {
                    $this->advanceWinnerLegacy($game, $winnerId);
                }
            }
            
            
            $response = $game->fresh()->load(['member1', 'member2']);
            
            if ($rankingUpdate && $rankingUpdate['success']) {
                $response->ranking_update = $rankingUpdate;
            }
            
            return response()->json($response);
        });
    }

    /**
     * Avanza al ganador de un partido de eliminatoria a la siguiente ronda
     * usando la información de pairing_info
     */
    private function advanceWinnerToNextRound(Game $game)
    {
        
        if ($game->status !== 'completed' || !$game->winner_id) {
            return false;
        }

        
        $isGroupStageMatch = $game->group_name && str_contains($game->group_name, 'Grupo');
        
        
        if ($isGroupStageMatch) {
            return false;
        }

        
        $nextRoundGames = Game::where('tournament_id', $game->tournament_id)
            ->where('round', $game->round + 1)
            ->whereIn('status', ['waiting_for_groups', 'waiting_for_winner'])
            ->get();

        foreach ($nextRoundGames as $nextGame) {
            $pairingInfo = json_decode($nextGame->pairing_info, true);
            
            
            if ($pairingInfo && $pairingInfo['type'] === 'game_winners') {
                
                $currentRoundGames = Game::where('tournament_id', $game->tournament_id)
                    ->where('round', $game->round)
                    ->orderBy('id')
                    ->get();
                
                $gameIndex = null;
                foreach ($currentRoundGames as $index => $roundGame) {
                    if ($roundGame->id === $game->id) {
                        $gameIndex = $index;
                        break;
                    }
                }
                
                if ($gameIndex === null) {
                    continue;
                }
                
                $assigned = false;
                
                
                if (!$nextGame->member1_id && 
                    isset($pairingInfo['participant1']) &&
                    $pairingInfo['participant1']['prev_round'] == $game->round &&
                    $pairingInfo['participant1']['game'] == $gameIndex) {
                    
                    $nextGame->member1_id = $game->winner_id;
                    $assigned = true;
                }
                
                
                if (!$nextGame->member2_id && 
                    isset($pairingInfo['participant2']) &&
                    $pairingInfo['participant2']['prev_round'] == $game->round &&
                    $pairingInfo['participant2']['game'] == $gameIndex) {
                    
                    $nextGame->member2_id = $game->winner_id;
                    $assigned = true;
                }
                
                if ($assigned) {
                    
                    if ($nextGame->member1_id && $nextGame->member2_id) {
                        $nextGame->status = 'pending';
                    }
                    $nextGame->save();
                    return true;
                }
            }
            
            else {
                
                $currentRoundGames = Game::where('tournament_id', $game->tournament_id)
                    ->where('round', $game->round)
                    ->orderBy('id')
                    ->get();
                
                $gameIndex = null;
                foreach ($currentRoundGames as $index => $roundGame) {
                    if ($roundGame->id === $game->id) {
                        $gameIndex = $index;
                        break;
                    }
                }
                
                if ($gameIndex === null) {
                    continue;
                }
                
                
                $nextGameIndex = floor($gameIndex / 2);
                
                
                $nextRoundGamesOrdered = Game::where('tournament_id', $game->tournament_id)
                    ->where('round', $game->round + 1)
                    ->orderBy('id')
                    ->get();
                
                if (isset($nextRoundGamesOrdered[$nextGameIndex]) && 
                    $nextRoundGamesOrdered[$nextGameIndex]->id === $nextGame->id) {
                    
                    
                    $slot = ($gameIndex % 2 === 0) ? 'member1_id' : 'member2_id';
                    
                    if (is_null($nextGame->{$slot})) {
                        $nextGame->{$slot} = $game->winner_id;
                        
                        
                        if ($nextGame->member1_id && $nextGame->member2_id) {
                            $nextGame->status = 'pending';
                        }
                        $nextGame->save();
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Método de respaldo para torneos de eliminación directa sin pairing_info
     * (mantener por compatibilidad con torneos antiguos)
     */
    private function advanceWinnerLegacy(Game $completedGame, int $winnerId)
    {
        
        if (!$completedGame->round) {
            return;
        }
        
        
        $roundGames = Game::where('tournament_id', $completedGame->tournament_id)
                            ->where('round', $completedGame->round)
                            ->orderBy('id', 'asc')
                            ->get();

        
        $gameIndex = null;
        foreach ($roundGames as $index => $roundGame) {
            if ($roundGame->id === $completedGame->id) {
                $gameIndex = $index;
                break;
            }
        }

        if ($gameIndex !== null) {
            
            $nextGameIndex = floor($gameIndex / 2);
            
            
            $nextRoundGames = Game::where('tournament_id', $completedGame->tournament_id)
                                    ->where('round', $completedGame->round + 1)
                                    ->whereIn('status', ['waiting_for_groups', 'waiting_for_winner', 'pending'])
                                    ->orderBy('id', 'asc')
                                    ->get();
            
            if (isset($nextRoundGames[$nextGameIndex])) {
                $nextGame = $nextRoundGames[$nextGameIndex];
                
                
                $slot = ($gameIndex % 2 === 0) ? 'member1_id' : 'member2_id';
                
                if (is_null($nextGame->{$slot})) {
                    $nextGame->{$slot} = $winnerId;
                    
                    
                    if ($nextGame->member1_id && $nextGame->member2_id) {
                        $nextGame->status = 'pending';
                    }
                    $nextGame->save();
                }
            }
        }
    }

    /**
     * Obtiene información del sistema de ranking (para debugging/información)
     * 
     * @return \Illuminate\Http\Response
     */
    public function getRankingSystemInfo()
    {
        return response()->json($this->getRankingSystemSummary());
    }
}
