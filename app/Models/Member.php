<?php

// app/Models/Member.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'ranking', 'age'];

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_registrations');
    }
}
