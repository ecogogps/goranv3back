<?php

// app/Models/Tournament.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    // Permite que todos los campos se asignen masivamente
    protected $guarded = [];

    public function members()
    {
        return $this->belongsToMany(Member::class, 'tournament_registrations')
                    ->withTimestamps(); // Para acceder a la fecha de inscripción
    }

    /**
     * Relación para obtener los partidos de este torneo.
     */
    public function games() // <-- CAMBIADO
    {
        return $this->hasMany(Game::class); // <-- CAMBIADO
    }
}
