<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liga extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'deporte_id',
        'pais',
        'provincia',
        'ciudad',
        'user_id',
        'celular'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Relación con Deporte
     * Una liga pertenece a un deporte
     */
    public function deporte()
    {
        return $this->belongsTo(Deporte::class);
    }

    /**
     * Relación con Clubs
     * Una liga tiene muchos clubs
     */
    public function clubs()
    {
        return $this->hasMany(Club::class);
    }

    /**
     * Relación con Categorias
     * Una liga tiene muchas categorías
     */
    public function categorias()
    {
        return $this->hasMany(Categoria::class);
    }
}
