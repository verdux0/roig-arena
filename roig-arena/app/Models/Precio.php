<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Precio extends Model
{
    use HasFactory;

    protected $table = 'precios';
    
    protected $fillable = [
        'evento_id',
        'sector_id',
        'precio',
        'disponible',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'precio' => 'decimal:2',
        'disponible' => 'boolean',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un precio pertenece a un evento
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    /**
     * Un precio pertenece a un sector
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    /**
     * Obtener el precio formateado
     */
    public function precioFormateado(): string
    {
        return number_format($this->precio, 2, ',', '.') . ' €';
    }

    /**
     * Verificar si está disponible
     */
    public function estaDisponible(): bool
    {
        return $this->disponible && $this->sector->activo;
    }

    /**
     * Scope: Solo precios disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('disponible', true)
                     ->whereHas('sector', function ($q) {
                         $q->where('activo', true);
                     });
    }

    /**
     * Scope: Precios de un evento específico
     */
    public function scopeDeEvento($query, $eventoId)
    {
        return $query->where('evento_id', $eventoId);
    }

    /**
     * Scope: Precios de un sector específico
     */
    public function scopeDeSector($query, $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }
}