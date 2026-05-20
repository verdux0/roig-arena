<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden asignar masivamente
     */
    protected $table = 'sectores';

    protected $fillable = [
        'nombre',
        'descripcion',
        'cantidad_filas',
        'cantidad_columnas',
        'color_hex',
        'activo',
        'fila_inicio',
        'fila_fin',
        'columna_inicio',
        'columna_fin',
        'posicion_x',
        'posicion_y',
        'orden_visual',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'activo' => 'boolean',
        'posicion_x' => 'decimal:2',
        'posicion_y' => 'decimal:2',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un sector tiene muchos asientos
     */
    public function asientos()
    {
        return $this->hasMany(Asiento::class);
    }

    /**
     * Un sector tiene muchos precios (uno por evento)
     */
    public function precios()
    {
        return $this->hasMany(Precio::class);
    }

    /**
     * Un sector está disponible en muchos eventos (a través de precios)
     */
    public function eventos()
    {
        return $this->belongsToMany(Evento::class, 'precios')
                    ->withPivot('precio', 'disponible')
                    ->withTimestamps();
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    // Método para obtener asientos del sector organizados
    public function obtenerAsientosOrganizados() {
        return $this->asientos()
            ->orderBy('numero_fila')
            ->orderBy('numero_asiento')
            ->get()
            ->groupBy('numero_fila')
            ->toArray();
    }

    public function contarDisponibles($eventoId): int
    {
        return $this->asientos()
            ->whereDoesntHave('estadoAsientos', function ($query) use ($eventoId) {
                $query->where('evento_id', $eventoId);
            })
            ->count();
    }

    public function contarReservados($eventoId): int
    {
        return $this->asientos()
            ->whereHas('estadoAsientos', function ($query) use ($eventoId) {
                $query->where('evento_id', $eventoId)
                      ->where('estado', 'reservado');
            })
            ->count();
    }

    public function contarOcupados($eventoId): int
    {
        return $this->asientos()
            ->whereHas('estadoAsientos', function ($query) use ($eventoId) {
                $query->where('evento_id', $eventoId)
                      ->where('estado', 'ocupado');
            })
            ->count();
    }

    /**
     * Verificar si el sector está activo globalmente
     */
    public function estaActivo(): bool
    {
        return $this->activo;
    }

    /**
     * Obtener el número total de asientos del sector
     */
    public function totalAsientos(): int
    {
        return $this->asientos()->count();
    }

    /**
     * Obtener asientos disponibles para un evento específico
     */
    public function asientosDisponiblesParaEvento($eventoId)
    {
        return $this->asientos()
            ->whereDoesntHave('estadoAsientos', function ($query) use ($eventoId) {
                $query->where('evento_id', $eventoId);
            })
            ->get();
    }

    /**
     * Scope: Solo sectores activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

}
