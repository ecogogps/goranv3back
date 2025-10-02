<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name', 
        'ranking', 
        'age',
        'cedula',
        'fecha_nacimiento',
        'genero',
        'pais',
        'provincia',
        'ciudad',
        'celular',
        'club_id',
        'user_id', 
        'drive_marca',
        'drive_modelo',
        'drive_tipo',
        'back_marca',
        'back_modelo',
        'back_tipo'
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_registrations');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    // Métodos helper para validar los tipos de caucho
    public static function getTiposCaucho()
    {
        return [
            'Antitopsping',
            'Liso',
            'Pupo Corto',
            'Pupo Largo',
            'Todos'
        ];
    }

    public static function getMarcasDrive()
    {
        return [
            'Friendship'
        ];
    }

    public static function getModelosDrive()
    {
        return [
            'Cross 729'
        ];
    }

    public static function getMarcasBack()
    {
        return [
            'Saviga'
        ];
    }

    public static function getModelosBack()
    {
        return [
            'V'
        ];
    }
}
