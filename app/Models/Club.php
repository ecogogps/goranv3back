<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nombre',
        'imagen',
        'user_id',
        'liga_id',
        'ruc',
        'pais',
        'provincia',
        'ciudad',
        'direccion',
        'celular',
        'google_maps_url',
        'representante_nombre',
        'representante_telefono',
        'representante_email',
        'admin1_nombre',
        'admin1_telefono',
        'admin1_email',
        'admin2_nombre',
        'admin2_telefono',
        'admin2_email',
        'admin3_nombre',
        'admin3_telefono',
        'admin3_email',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function liga()
    {
        return $this->belongsTo(Liga::class);
    }
}
