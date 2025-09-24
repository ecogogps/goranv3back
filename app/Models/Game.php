<?php

// app/Models/Game.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tournament_id',
        'round',
        'group_name',
        'member1_id',
        'member2_id',
        'points_member1', // Usamos estos en lugar de score1/score2 por consistencia
        'points_member2',
        'winner_id',
        'status',
        'elimination_game_id',
    ];

    /**
     * Relación con el torneo al que pertenece el partido.
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * Relación con el primer participante.
     */
    public function member1(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member1_id');
    }

    /**
     * Relación con el segundo participante.
     */
    public function member2(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'member2_id');
    }

    /**
     * Relación con el miembro que ganó el partido.
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'winner_id');
    }
}
