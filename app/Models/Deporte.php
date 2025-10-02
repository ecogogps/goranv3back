<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deporte extends Model
{
    use HasFactory;

    protected $table = 'deportes';

    protected $fillable = [
        'nombre'
    ];

    protected $hidden = [];

    protected $casts = [];

    public function ligas()
    {
        return $this->hasMany(Liga::class);
    }
}
