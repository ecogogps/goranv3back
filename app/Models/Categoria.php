<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = [
        'liga_id',
        'code_start',
        'code_end'
    ];

    protected $casts = [
        'liga_id' => 'integer',
        'code_start' => 'integer',
        'code_end' => 'integer'
    ];

    /**
     * Relación con Liga
     */
    public function liga()
    {
        return $this->belongsTo(Liga::class);
    }
}
