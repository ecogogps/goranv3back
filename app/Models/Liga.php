<?php

// app/Models/Liga.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Liga extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'pais',
        'provincia',
        'ciudad'
    ];

    public function clubs()
    {
        return $this->hasMany(Club::class);
    }
}