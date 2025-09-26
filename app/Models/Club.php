<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nombre',
        'imagen'
    ];

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}