<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artista extends Model
{
    use HasFactory;

    protected $table = 'artistas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen_url',
    ];

    /**
     * Relación con Evento
     */
    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'artista_evento')
                    ->withTimestamps();
    }

}
