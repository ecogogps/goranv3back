<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\RankingHistory;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BracketController extends Controller
{
    
    public function generateBracket(Request $request, Tournament $tournament)
    {
        
        $participants = $tournament->members()->orderBy('pivot_created_at')->get();

        if ($participants->count() < 2) {
            return response()->json(['message' => 'No hay suficientes participantes inscritos para generar el cuadro.'], 400);
        }

        
        $seedingType = $request->query('seeding_type', 'aleatorio');
        $participantsNumber = (int)$request->query('participants', $participants->count()); 
        $eliminationType = $tournament->elimination_type;
        
        
        $advancersPerGroup = (int)$request->query('advancers_per_group', 2);
        $groupsNumber = (int)$request->query('groups_number', 4);
        $rounds = (int)$request->query('rounds', 1);

        
        if ($eliminationType === 'groups') {
            
            if ($participantsNumber < $groupsNumber * 2) {
                return response()->json([
                    'message' => "Se necesitan al menos " . ($groupsNumber * 2) . " participantes para crear {$groupsNumber} grupos con mínimo 2 participantes por grupo. Participantes disponibles: {$participantsNumber}."
                ], 400);
            }

            
            $participantsPerGroup = floor($participantsNumber / $groupsNumber);
            if ($advancersPerGroup >= $participantsPerGroup) {
                return response()->json([
                    'message' => "El número de clasificados por grupo ({$advancersPerGroup}) debe ser menor al número de participantes por grupo ({$participantsPerGroup})."
                ], 400);
            }

            
            $totalAdvancers = $groupsNumber * $advancersPerGroup;
            if ($totalAdvancers < 2) {
                return response()->json([
                    'message' => "Se necesitan al menos 2 clasificados totales para la fase eliminatoria. Actualmente: {$totalAdvancers} clasificados."
                ], 400);
            }

            
            if ($groupsNumber < 2) {
                return response()->json(['message' => 'Se necesitan al menos 2 grupos para el formato de eliminación por grupos.'], 400);
            }

            if ($rounds < 1 || $rounds > 2) {
                return response()->json(['message' => 'El número de vueltas debe ser 1 (ida) o 2 (ida y vuelta).'], 400);
            }

            if ($advancersPerGroup < 1) {
                return response()->json(['message' => 'Debe clasificar al menos 1 participante por grupo.'], 400);
            }
        }

        
        $sortedParticipants = $this->sortParticipants($participants, $seedingType);
        
        
        $gamesData = $this->generateGamesArray(
            $sortedParticipants, 
            $tournament->id, 
            $eliminationType, 
            $participantsNumber,
            $advancersPerGroup,
            $groupsNumber,
            $rounds
        );
        
        
        $tournament->games()->delete();
        if (!empty($gamesData)) {
            Game::insert($gamesData);
        }

        
        return response()->json(['message' => 'Cuadro generado correctamente.'], 201);
    }

    /**
     *  Maneja GET /tournaments/{tournament}/bracket
     *  Obtiene los partidos ya generados desde la base de datos.
     */
    public function getBracket(Tournament $tournament)
    {
        
        $games = $tournament->games()->with('member1.club', 'member2.club')->get();
        
        // Transformar los datos para incluir las URLs de las imágenes de los clubes
        $games->transform(function ($game) {
            if ($game->member1 && $game->member1->club && $game->member1->club->imagen) {
                $game->member1->club->imagen_url = 'https://trollopy-ephraim-hypoxanthic.ngrok-free.dev/storage/' . $game->member1->club->imagen;
            } else if ($game->member1 && $game->member1->club) {
                $game->member1->club->imagen_url = null;
            }
            
            if ($game->member2 && $game->member2->club && $game->member2->club->imagen) {
                $game->member2->club->imagen_url = 'https://trollopy-ephraim-hypoxanthic.ngrok-free.dev/storage/' . $game->member2->club->imagen;
            } else if ($game->member2 && $game->member2->club) {
                $game->member2->club->imagen_url = null;
            }
            
            return $game;
        });
        
        return response()->json($games);
    }
    
    /**
     *  GET /tournaments/{tournament}/standings
     *  Calcula y devuelve la tabla de posiciones final para un torneo.
     */
    public function getStandings(Tournament $tournament)
    {
        $allGames = $tournament->games()->with('member1.club', 'member2.club')->get();
    
        $allGamesCompleted = $allGames->every(fn($game) => $game->status === 'completed');
    
        if (!$allGamesCompleted) {
            return response()->json(['message' => 'El torneo aún no ha finalizado. Complete todos los partidos para ver la tabla de posiciones.'], 400);
        }
    
        $standings = [];
        $allGames->pluck('member1')->merge($allGames->pluck('member2'))->unique('id')->filter()->each(function ($member) use (&$standings) {
            $standings[$member->id] = [
                'member_id' => $member->id,
                'name' => $member->name,
                'ranking' => $member->ranking,
                'points' => 0, 
                'wins' => 0,
                'draws' => 0, 
                'losses' => 0,
                'games_played' => 0,
                'club' => $member->club ? [
                    'id' => $member->club->id,
                    'nombre' => $member->club->nombre,
                    'imagen_url' => $member->club->imagen ? 'https://trollopy-ephraim-hypoxanthic.ngrok-free.dev/storage/' . $member->club->imagen : null
                ] : null
            ];
        });
    
        foreach ($allGames as $game) {
            if (!$game->member1_id || !$game->member2_id) continue;
    
            $member1Id = $game->member1_id;
            $member2Id = $game->member2_id;
            
            $standings[$member1Id]['games_played']++;
            $standings[$member2Id]['games_played']++;
    
            if ($game->winner_id === null) {
                $standings[$member1Id]['points'] += 1;
                $standings[$member2Id]['points'] += 1;
                $standings[$member1Id]['draws']++;
                $standings[$member2Id]['draws']++;
            } else {
                $winnerId = $game->winner_id;
                $loserId = ($winnerId == $member1Id) ? $member2Id : $member1Id;
    
                $standings[$winnerId]['points'] += 3;
                $standings[$winnerId]['wins']++;
                $standings[$loserId]['losses']++;
            }
        }
    
        // ✅ AGREGAR CAMBIO DE RANKING DEL TORNEO
        foreach ($standings as $memberId => &$standing) {
            $firstRanking = \App\Models\RankingHistory::forMember($memberId)
                ->where('tournament_id', $tournament->id)
                ->oldest()
                ->first();
                
            $lastRanking = \App\Models\RankingHistory::forMember($memberId)
                ->where('tournament_id', $tournament->id)
                ->latest()
                ->first();
            
            if ($firstRanking && $lastRanking) {
                $standing['ranking_change'] = $lastRanking->ranking - $firstRanking->previous_ranking;
                $standing['initial_ranking'] = $firstRanking->previous_ranking;
                $standing['final_ranking'] = $lastRanking->ranking;
            } else {
                $standing['ranking_change'] = null;
                $standing['initial_ranking'] = $standing['ranking'];
                $standing['final_ranking'] = $standing['ranking'];
            }
        }
    
        usort($standings, function($a, $b) {
            if ($b['points'] !== $a['points']) return $b['points'] - $a['points'];
            if ($b['wins'] !== $a['wins']) return $b['wins'] - $a['wins'];
            return $a['losses'] - $b['losses'];
        });
    
        return response()->json(array_values($standings));
    }

    // ... resto de métodos privados sin cambios ...

    private function sortParticipants(Collection $participants, string $seedingType): Collection
    {
        return match ($seedingType) {
            'tradicional' => $this->applyCulebritaSeeding($participants),
            'aleatorio' => $participants->shuffle(),
            'secuencial' => $participants,
            default => $participants,
        };
    }

    private function applyCulebritaSeeding(Collection $participants): Collection
    {
        $sortedByRanking = $participants->sortByDesc('ranking')->values();
        $numGroups = max(1, floor($participants->count() / 4));
        $groups = array_fill(0, $numGroups, []);
        
        foreach ($sortedByRanking as $index => $participant) {
            $groupRound = floor($index / $numGroups);
            
            if ($groupRound % 2 === 0) {
                $groupIndex = $index % $numGroups;
            } else {
                $groupIndex = $numGroups - 1 - ($index % $numGroups);
            }
            
            $groups[$groupIndex][] = $participant;
        }
        
        $result = [];
        foreach ($groups as $group) {
            $result = array_merge($result, $group);
        }
        
        return collect($result);
    }

    private function generateGamesArray(
        Collection $participants, 
        int $tournamentId, 
        string $eliminationType, 
        int $participantsNumber,
        int $advancersPerGroup = 2,
        int $groupsNumber = 4,
        int $rounds = 1
    ): array {
        return match ($eliminationType) {
            'direct' => $this->createDirectEliminationGames($participants, $tournamentId),
            'groups' => $this->createGroupPlayoffGames($participants, $tournamentId, $participantsNumber, $advancersPerGroup, $groupsNumber, $rounds),
            'round_robin' => $this->createRoundRobinGames($participants, $tournamentId),
            'mixed' => $this->createGroupPlayoffGames($participants, $tournamentId, $participantsNumber, $advancersPerGroup, $groupsNumber, $rounds),
            default => [],
        };
    }

    private function createDirectEliminationGames(Collection $participants, int $tournamentId): array
    {
        $games = [];
        $players = $participants->values()->all();
        $numPlayers = count($players);
    
        if ($numPlayers < 2) {
            return [];
        }
        
        $totalRounds = ceil(log($numPlayers, 2));
        $bracketSize = pow(2, $totalRounds);
        
        while (count($players) < $bracketSize) {
            $players[] = null;
        }
    
        $roundNumber = 1;
        $currentRoundPlayers = $players;
    
        while (count($currentRoundPlayers) > 1) {
            $player1 = array_shift($currentRoundPlayers);
            $player2 = array_shift($currentRoundPlayers);
    
            if ($player1 || $player2) {
                $games[] = [
                    'tournament_id' => $tournamentId,
                    'round' => $roundNumber,
                    'group_name' => null,
                    'member1_id' => $player1 ? $player1->id : null,
                    'member2_id' => $player2 ? $player2->id : null,
                    'status' => $player1 && $player2 ? 'pending' : 'waiting_for_winner',
                    'points_member1' => null,
                    'points_member2' => null,
                    'elimination_game_id' => null,
                    'pairing_info' => null,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }
        }
    
        $numGamesInNextRound = $bracketSize / 4;
        $roundNumber++;
    
        while ($numGamesInNextRound >= 1) {
            for ($i = 0; $i < $numGamesInNextRound; $i++) {
                $games[] = [
                    'tournament_id' => $tournamentId,
                    'round' => $roundNumber,
                    'group_name' => null,
                    'member1_id' => null,
                    'member2_id' => null,
                    'status' => 'waiting_for_winner',
                    'points_member1' => null,
                    'points_member2' => null,
                    'elimination_game_id' => null,
                    'pairing_info' => null,
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }
            $numGamesInNextRound /= 2;
            $roundNumber++;
        }
    
        return $games;
    }
    
    private function createGroupPlayoffGames(
        Collection $participants, 
        int $tournamentId, 
        int $participantsNumber,
        int $advancersPerGroup,
        int $groupsNumber,
        int $rounds
    ): array {
        $participantsArray = $participants->values()->all();
        $numParticipants = $participants->count();
        
        
        $participantsToDistribute = min($numParticipants, $participantsNumber);
        
        
        $groups = array_fill(0, $groupsNumber, []);
        
        
        for ($i = 0; $i < $participantsToDistribute; $i++) {
            $groupIndex = $i % $groupsNumber;
            $round = floor($i / $groupsNumber);

            if ($round % 2 != 0) {
                $groupIndex = $groupsNumber - 1 - $groupIndex;
            }
            $groups[$groupIndex][] = $participantsArray[$i];
        }

        $allGames = [];
        
        
        foreach ($groups as $index => $groupParticipants) {
            if (count($groupParticipants) > 1) {
                $groupName = 'Grupo ' . chr(65 + $index);
                $groupGames = $this->createRoundRobinGames(collect($groupParticipants), $tournamentId, $groupName, $rounds);
                $allGames = array_merge($allGames, $groupGames);
            }
        }
        
        
        $totalAdvancers = $groupsNumber * $advancersPerGroup;
        $eliminationRounds = $totalAdvancers > 1 ? ceil(log($totalAdvancers, 2)) : 0;
        
        
        $this->createEliminationPhaseStructure($tournamentId, $groupsNumber, $advancersPerGroup, $eliminationRounds, $allGames);
        
        return $allGames;
    }

    private function createEliminationPhaseStructure(
        int $tournamentId,
        int $numGroups,
        int $advancersPerGroup,
        int $eliminationRounds,
        array &$allGames
    ): void {
        $totalAdvancers = $numGroups * $advancersPerGroup;
        
        if ($totalAdvancers < 2) return;
        
        $currentRound = 1;
        $playersInRound = $totalAdvancers;
        
        while ($playersInRound > 1 && $currentRound <= $eliminationRounds) {
            $gamesInRound = floor($playersInRound / 2);
            $roundName = $this->getEliminationRoundName($currentRound, $eliminationRounds);
            
            for ($gameNum = 0; $gameNum < $gamesInRound; $gameNum++) {
                
                $pairingInfo = $this->calculateEliminationPairing($gameNum, $currentRound, $numGroups, $advancersPerGroup);
                
                $allGames[] = [
                    'tournament_id' => $tournamentId,
                    'round' => $currentRound,
                    'group_name' => $roundName,
                    'member1_id' => null,
                    'member2_id' => null,
                    'status' => 'waiting_for_groups',
                    'elimination_game_id' => null,
                    'points_member1' => null,
                    'points_member2' => null,
                    'pairing_info' => json_encode($pairingInfo), 
                    'created_at' => now()->toDateTimeString(),
                    'updated_at' => now()->toDateTimeString(),
                ];
            }
            
            $playersInRound = $gamesInRound;
            $currentRound++;
        }
    }

    private function calculateEliminationPairing(int $gameNum, int $round, int $numGroups, int $advancersPerGroup): array
    {
        if ($round == 1) {
            
            if ($advancersPerGroup == 2 && $numGroups >= 2) {
                
                if ($numGroups == 2) {
                    
                    if ($gameNum == 0) {
                        return [
                            'type' => 'group_winners',
                            'participant1' => ['group' => 0, 'position' => 1], // 1° Grupo A
                            'participant2' => ['group' => 1, 'position' => 2], // 2° Grupo B
                        ];
                    } else {
                        return [
                            'type' => 'group_winners',
                            'participant1' => ['group' => 1, 'position' => 1], // 1° Grupo B
                            'participant2' => ['group' => 0, 'position' => 2], // 2° Grupo A
                        ];
                    }
                } else {
                    
                    $group1 = $gameNum % $numGroups;
                    $group2 = ($gameNum + floor($numGroups/2)) % $numGroups;
                    $position1 = ($gameNum < floor($numGroups / 2)) ? 1 : 2;
                    $position2 = 3 - $position1;
                    
                    return [
                        'type' => 'group_winners',
                        'participant1' => ['group' => $group1, 'position' => $position1],
                        'participant2' => ['group' => $group2, 'position' => $position2],
                    ];
                }
            } else {
                
                $group1 = $gameNum * 2;
                $group2 = $group1 + 1;
                
                return [
                    'type' => 'group_winners',
                    'participant1' => ['group' => $group1, 'position' => 1],
                    'participant2' => ['group' => $group2, 'position' => 1],
                ];
            }
        } else {
            
            $prevGame1 = $gameNum * 2;
            $prevGame2 = $prevGame1 + 1;
            
            return [
                'type' => 'game_winners',
                'participant1' => ['prev_round' => $round - 1, 'game' => $prevGame1],
                'participant2' => ['prev_round' => $round - 1, 'game' => $prevGame2],
                'description' => "Ganador Semifinal " . ($prevGame1 + 1) . " vs Ganador Semifinal " . ($prevGame2 + 1)
            ];
        }
    }

    private function getEliminationRoundName(int $round, int $totalRounds): string
    {
        $remainingRounds = $totalRounds - $round + 1;
        
        return match($remainingRounds) {
            1 => 'Final',
            2 => 'Semifinal',
            3 => 'Cuartos de Final',
            4 => 'Octavos de Final',
            default => 'Eliminatoria Ronda ' . $round
        };
    }

    private function createRoundRobinGames(Collection $participants, int $tournamentId, ?string $groupName = null, int $rounds = 1): array
    {
        $games = [];
        $players = $participants->values()->all();
        if (count($players) < 2) return [];

        if (count($players) % 2 !== 0) {
            $players[] = null;
        }
        
        $numRounds = count($players) - 1;
        $numPlayers = count($players);
        
        
        for ($vuelta = 1; $vuelta <= $rounds; $vuelta++) {
            for ($round = 0; $round < $numRounds; $round++) {
                for ($i = 0; $i < $numPlayers / 2; $i++) {
                    $player1 = $players[$i];
                    $player2 = $players[$numPlayers - 1 - $i];
                    
                    if ($player1 && $player2) {
                        
                        if ($vuelta == 2) {
                            [$player1, $player2] = [$player2, $player1];
                        }
                        
                        $games[] = [
                            'tournament_id' => $tournamentId,
                            'round' => $groupName ? null : (($vuelta - 1) * $numRounds + $round + 1),
                            'group_name' => $groupName,
                            'member1_id' => $player1->id,
                            'member2_id' => $player2->id,
                            'status' => 'pending',
                            'points_member1' => null,
                            'points_member2' => null,
                            'elimination_game_id' => null,
                            'pairing_info' => $rounds > 1 ? json_encode(['vuelta' => $vuelta]) : null,
                            'created_at' => now()->toDateTimeString(),
                            'updated_at' => now()->toDateTimeString(),
                        ];
                    }
                }
                
                $lastPlayer = array_pop($players);
                array_splice($players, 1, 0, [$lastPlayer]);
            }
        }
        
        return $games;
    }

    public static function placeAdvancersInElimination(int $tournamentId, string $groupName, int $advancersPerGroup)
    {
        
        $groupGames = Game::where('tournament_id', $tournamentId)
                            ->where('group_name', $groupName)
                            ->get();

        
        if ($groupGames->count() === 0 || !$groupGames->every(fn($game) => $game->status === 'completed')) {
            return; 
        }

        
        $standings = [];
        $groupGames->pluck('member1_id')->merge($groupGames->pluck('member2_id'))->unique()->filter()->each(function ($memberId) use (&$standings) {
            $standings[$memberId] = ['points' => 0, 'wins' => 0, 'member_id' => $memberId];
        });
        
        foreach ($groupGames as $game) {
            if ($game->winner_id) {
                $standings[$game->winner_id]['points'] += 2;
                $standings[$game->winner_id]['wins']++;
                $loserId = ($game->winner_id == $game->member1_id) ? $game->member2_id : $game->member1_id;
                if (isset($standings[$loserId])) {
                    $standings[$loserId]['points'] += 1;
                }
            }
        }
        
        usort($standings, function ($a, $b) {
            if ($b['points'] !== $a['points']) return $b['points'] - $a['points'];
            return $b['wins'] - $a['wins'];
        });

        $advancingMembers = collect($standings)->slice(0, $advancersPerGroup);
        
        
        $groupIndex = ord(substr($groupName, -1)) - ord('A');
        
        
        foreach ($advancingMembers as $position => $memberData) {
            $memberId = $memberData['member_id'];
            $positionInGroup = $position + 1; 
            
            
            $eliminationGames = Game::where('tournament_id', $tournamentId)
                ->where('group_name', '!=', $groupName)
                ->where('status', 'waiting_for_groups')
                ->whereNotNull('pairing_info')
                ->get();
                
            foreach ($eliminationGames as $game) {
                $pairingInfo = json_decode($game->pairing_info, true);
                
                if ($pairingInfo && $pairingInfo['type'] === 'group_winners') {
                    $assigned = false;
                    
                    
                    if (!$game->member1_id && 
                        $pairingInfo['participant1']['group'] == $groupIndex && 
                        $pairingInfo['participant1']['position'] == $positionInGroup) {
                        
                        $game->member1_id = $memberId;
                        $assigned = true;
                    }
                    
                    
                    if (!$game->member2_id && 
                        $pairingInfo['participant2']['group'] == $groupIndex && 
                        $pairingInfo['participant2']['position'] == $positionInGroup) {
                        
                        $game->member2_id = $memberId;
                        $assigned = true;
                    }
                    
                    if ($assigned) {
                        
                        if ($game->member1_id && $game->member2_id) {
                            $game->status = 'pending';
                        }
                        $game->save();
                        break; 
                    }
                }
            }
        }
    }
}
