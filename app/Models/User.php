<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Get the profile associated with the user (Liga, Club, or Member).
     * This uses an accessor to dynamically get the correct profile relationship.
     */
    public function getProfileAttribute()
    {
        // The relationship name is the role in lowercase
        $relationship = strtolower($this->role);

        // Check if the relationship method exists to avoid errors
        if (method_exists($this, $relationship)) {
            return $this->{$relationship};
        }
        
        return null;
    }

    /**
     * Define the one-to-one relationship with Liga.
     */
    public function liga(): HasOne
    {
        return $this->hasOne(Liga::class);
    }

    /**
     * Define the one-to-one relationship with Club.
     */
    public function club(): HasOne
    {
        return $this->hasOne(Club::class);
    }

    /**
     * Define the one-to-one relationship with Member.
     */
    public function miembro(): HasOne
    {
        return $this->hasOne(Member::class);
    }
}

