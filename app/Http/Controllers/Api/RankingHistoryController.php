<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\RankingHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RankingHistoryController extends Controller
{
    /**
     * Obtiene el historial de ranking de un miembro (últimos 12 meses agrupado por mes)
     * GET /api/members/{member}/ranking-history
     */
    public function show(Member $member): JsonResponse
    {
        $history = RankingHistory::forMember($member->id)
            ->lastMonths(12)
            ->orderBy('created_at', 'desc')
            ->with(['game', 'tournament'])
            ->get();

        // Agrupar por mes para el gráfico
        $monthlyData = $this->groupByMonth($history);

        return response()->json([
            'member' => [
                'id' => $member->id,
                'name' => $member->name,
                'current_ranking' => $member->ranking,
            ],
            'history' => $history,
            'monthly_chart_data' => $monthlyData,
            'latest_change' => $this->getLatestChange($member->id),
        ]);
    }

    /**
     * Obtiene el último cambio de ranking del miembro
     */
    private function getLatestChange($memberId)
    {
        $latest = RankingHistory::forMember($memberId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$latest) {
            return null;
        }

        return [
            'change' => $latest->change,
            'ranking' => $latest->ranking,
            'previous_ranking' => $latest->previous_ranking,
            'date' => $latest->created_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Agrupa el historial por mes para mostrar en gráfico
     * Toma el último ranking de cada mes
     */
    private function groupByMonth($history)
    {
        $grouped = $history->groupBy(function ($item) {
            return $item->created_at->format('Y-m');
        });

        $monthlyData = [];
        
        foreach ($grouped as $month => $records) {
            // Tomar el último ranking del mes
            $lastRecord = $records->first(); // Ya está ordenado desc
            
            $monthlyData[] = [
                'month' => $month,
                'month_name' => $lastRecord->created_at->translatedFormat('M'),
                'year' => $lastRecord->created_at->year,
                'ranking' => $lastRecord->ranking,
                'date' => $lastRecord->created_at->format('Y-m-d'),
            ];
        }

        // Revertir para que el gráfico muestre de pasado a presente
        return array_reverse($monthlyData);
    }

    /**
     * Obtiene el historial detallado (todos los cambios)
     * GET /api/members/{member}/ranking-history/detailed
     */
    public function detailed(Member $member): JsonResponse
    {
        $history = RankingHistory::forMember($member->id)
            ->with(['game.member1', 'game.member2', 'tournament'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($history);
    }

    /**
     * Obtiene estadísticas de ranking
     * GET /api/members/{member}/ranking-stats
     */
    public function stats(Member $member): JsonResponse
    {
        $history = RankingHistory::forMember($member->id)->get();

        if ($history->isEmpty()) {
            return response()->json([
                'message' => 'No hay historial disponible',
                'stats' => null
            ]);
        }

        $wins = $history->where('change', '>', 0)->count();
        $losses = $history->where('change', '<', 0)->count();
        $totalGames = $wins + $losses;

        $stats = [
            'current_ranking' => $member->ranking,
            'highest_ranking' => $history->max('ranking'),
            'lowest_ranking' => $history->min('ranking'),
            'total_games' => $totalGames,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $totalGames > 0 ? round(($wins / $totalGames) * 100, 2) : 0,
            'total_points_gained' => $history->where('change', '>', 0)->sum('change'),
            'total_points_lost' => abs($history->where('change', '<', 0)->sum('change')),
            'average_change_per_game' => round($history->avg('change'), 2),
            'biggest_win' => $history->where('change', '>', 0)->max('change'),
            'biggest_loss' => $history->where('change', '<', 0)->min('change'),
        ];

        return response()->json($stats);
    }
}
