<?php

// app/Models/Club.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nombre',
        'imagen',
        'liga_id'
    ];

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function liga()
    {
        return $this->belongsTo(Liga::class);
    }
}
