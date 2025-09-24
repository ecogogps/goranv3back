<?php

namespace App\Http\Controllers\Api\Traits;

use App\Models\Member;
use Illuminate\Support\Facades\Log;

trait ManagesRanking
{
    /**
     * Tabla de puntos de intercambio basada en la diferencia de ranking
     * 
     * @return array
     */
    private function getExchangePointsTable()
    {
        return [
            ['min' => 0, 'max' => 12, 'expected' => 8, 'unexpected' => 8],
            ['min' => 13, 'max' => 37, 'expected' => 7, 'unexpected' => 10],
            ['min' => 38, 'max' => 62, 'expected' => 6, 'unexpected' => 13],
            ['min' => 63, 'max' => 87, 'expected' => 5, 'unexpected' => 16],
            ['min' => 88, 'max' => 112, 'expected' => 4, 'unexpected' => 20],
            ['min' => 113, 'max' => 137, 'expected' => 3, 'unexpected' => 25],
            ['min' => 138, 'max' => 162, 'expected' => 2, 'unexpected' => 30],
            ['min' => 163, 'max' => 187, 'expected' => 2, 'unexpected' => 35],
            ['min' => 188, 'max' => 212, 'expected' => 1, 'unexpected' => 40],
            ['min' => 213, 'max' => 237, 'expected' => 1, 'unexpected' => 45],
            ['min' => 238, 'max' => PHP_INT_MAX, 'expected' => 0, 'unexpected' => 50],
        ];
    }

    /**
     * Calcula y actualiza los rankings de los jugadores después de un partido
     * 
     * @param int $winnerId ID del jugador ganador
     * @param int $loserId ID del jugador perdedor
     * @return array Información sobre la actualización de ranking
     */
    public function updatePlayersRanking($winnerId, $loserId)
    {
        try {
            
            $winner = Member::find($winnerId);
            $loser = Member::find($loserId);

            if (!$winner || !$loser) {
                Log::warning("No se pudieron encontrar los jugadores para actualizar ranking", [
                    'winner_id' => $winnerId,
                    'loser_id' => $loserId
                ]);
                return [
                    'success' => false,
                    'message' => 'Jugadores no encontrados'
                ];
            }

            
            $winnerRanking = $winner->ranking ?? 1000;
            $loserRanking = $loser->ranking ?? 1000;

            
            $rankingDifference = abs($winnerRanking - $loserRanking);

            
            $higherRankedPlayer = $winnerRanking >= $loserRanking ? 'winner' : 'loser';

            
            $isExpectedResult = ($winnerRanking >= $loserRanking);

            
            $exchangePoints = $this->getExchangePointsForDifference($rankingDifference, $isExpectedResult);

            
            $newWinnerRanking = $winnerRanking + $exchangePoints;
            $newLoserRanking = $loserRanking - $exchangePoints;

            
            $winner->update(['ranking' => $newWinnerRanking]);
            $loser->update(['ranking' => $newLoserRanking]);

            Log::info("Rankings actualizados", [
                'winner' => [
                    'id' => $winnerId,
                    'name' => $winner->name,
                    'old_ranking' => $winnerRanking,
                    'new_ranking' => $newWinnerRanking,
                    'change' => $exchangePoints
                ],
                'loser' => [
                    'id' => $loserId,
                    'name' => $loser->name,
                    'old_ranking' => $loserRanking,
                    'new_ranking' => $newLoserRanking,
                    'change' => -$exchangePoints
                ],
                'difference' => $rankingDifference,
                'expected_result' => $isExpectedResult,
                'exchange_points' => $exchangePoints
            ]);

            return [
                'success' => true,
                'winner' => [
                    'id' => $winnerId,
                    'name' => $winner->name,
                    'old_ranking' => $winnerRanking,
                    'new_ranking' => $newWinnerRanking,
                    'change' => $exchangePoints
                ],
                'loser' => [
                    'id' => $loserId,
                    'name' => $loser->name,
                    'old_ranking' => $loserRanking,
                    'new_ranking' => $newLoserRanking,
                    'change' => -$exchangePoints
                ],
                'ranking_difference' => $rankingDifference,
                'expected_result' => $isExpectedResult,
                'exchange_points' => $exchangePoints
            ];

        } catch (\Exception $e) {
            Log::error("Error actualizando rankings", [
                'winner_id' => $winnerId,
                'loser_id' => $loserId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error actualizando rankings: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene los puntos de intercambio según la diferencia de ranking
     * 
     * @param int $difference Diferencia absoluta entre rankings
     * @param bool $isExpectedResult Si el resultado fue esperado o no
     * @return int Puntos a intercambiar
     */
    private function getExchangePointsForDifference($difference, $isExpectedResult)
    {
        $table = $this->getExchangePointsTable();

        foreach ($table as $range) {
            if ($difference >= $range['min'] && $difference <= $range['max']) {
                return $isExpectedResult ? $range['expected'] : $range['unexpected'];
            }
        }

        
        return $isExpectedResult ? 0 : 50;
    }

    /**
     * Verifica si un torneo afecta el ranking
     * 
     * @param \App\Models\Tournament $tournament
     * @return bool
     */
    public function tournamentAffectsRanking($tournament)
    {
        
        
        return $tournament && 
               isset($tournament->affects_ranking) && 
               (bool) $tournament->affects_ranking;
    }

    /**
     * Procesa la actualización de ranking para un partido completado
     * Solo procesa si:
     * - El torneo afecta el ranking
     * - El partido tiene un ganador definido (no es empate)
     * - Ambos jugadores existen
     * 
     * @param \App\Models\Game $game
     * @return array|null Resultado de la actualización o null si no se procesó
     */
    public function processGameRankingUpdate($game)
    {
        
        if ($game->status !== 'completed' || !$game->winner_id) {
            return null;
        }

        
        if (!$this->tournamentAffectsRanking($game->tournament)) {
            return null;
        }

        
        $winnerId = $game->winner_id;
        $loserId = ($game->member1_id === $winnerId) ? $game->member2_id : $game->member1_id;

        
        if (!$winnerId || !$loserId) {
            return null;
        }

        
        return $this->updatePlayersRanking($winnerId, $loserId);
    }

    /**
     * Obtiene un resumen de la tabla de puntos de intercambio para debugging
     * 
     * @return array
     */
    public function getRankingSystemSummary()
    {
        return [
            'system_name' => 'USA Table Tennis Simplified System',
            'default_ranking' => 1000,
            'exchange_table' => $this->getExchangePointsTable(),
            'description' => 'Sistema basado en diferencia de rankings donde el ganador gana puntos y el perdedor los pierde. Los puntos intercambiados dependen de la diferencia inicial y si el resultado fue esperado o inesperado.'
        ];
    }
}
