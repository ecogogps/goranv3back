<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankingHistory extends Model
{
    use HasFactory;

    protected $table = 'ranking_history';

    protected $fillable = [
        'member_id',
        'ranking',
        'previous_ranking',
        'change',
        'game_id',
        'tournament_id',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Scope para obtener historial de últimos N meses
     */
    public function scopeLastMonths($query, $months = 12)
    {
        return $query->where('created_at', '>=', now()->subMonths($months));
    }

    /**
     * Scope para un miembro específico
     */
    public function scopeForMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }
}