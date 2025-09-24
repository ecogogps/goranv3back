public static function placeAdvancersInElimination(int $tournamentId, string $groupName, int $advancersPerGroup)
{
    $tournament = Tournament::find($tournamentId);

    $groupGames = Game::where('tournament_id', $tournamentId)
                        ->where('group_name', $groupName)
                        ->get();

    if ($groupGames->count() === 0 || !$groupGames->every(fn($game) => $game->status === 'completed')) {
        return; 
    }

    // 1. Calcular posiciones del grupo
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

    // 🔹 REAPLICAR SIEMBRA SOLO PARA GROUPS Y MIXED
    if (in_array($tournament->elimination_type, ['groups', 'mixed'])) {
        $controller = app(\App\Http\Controllers\Api\BracketController::class);
        $advancingMembers = $controller->sortParticipants($advancingMembers, $tournament->seeding_type);
    }

    // ... aquí sigue la lógica de asignar a elimination games
}
