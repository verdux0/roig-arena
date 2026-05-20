<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'eventos';

    protected $fillable = [
        'nombre',
        'descripcion_corta',
        'descripcion_larga',
        'poster_url',
        'poster_ancho_url',
        'fecha',
        'hora',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un evento tiene muchos precios (uno por sector)
     */
    public function precios()
    {
        return $this->hasMany(Precio::class);
    }

    /**
     * Un evento tiene sectores disponibles (a través de precios)
     */
    public function sectores()
    {
        return $this->belongsToMany(Sector::class, 'precios')
                    ->withPivot('precio', 'disponible')
                    ->withTimestamps();
    }

    /**
     * Marcar sector como agotado (disponible=false)
     */
    public function marcarSectorAgotado($sectorId)
    {
        $this->sectores()->updateExistingPivot($sectorId, ['disponible' => false]);
    }

    /**
     * Actualizar el campo disponible de un sector para este evento.
     */
    public function actualizarDisponibilidadSector($sectorId, bool $disponible): bool
    {
        $precio = $this->precios()
            ->where('sector_id', $sectorId)
            ->first();

        if (!$precio) {
            return false;
        }

        if ($precio->disponible === $disponible) {
            return $disponible;
        }

        $this->sectores()->updateExistingPivot($sectorId, ['disponible' => $disponible]);

        return $disponible;
    }

    /**
     * Comprobar el evento y ajustar la disponibilidad de todos sus sectores.
     *
     * Esto permite reutilizar la comprobación tras compras u otras acciones
     * y mantener el campo `disponible` en la tabla `precios` sincronizado.
     *
     * @return bool True si el evento tiene al menos un sector disponible.
     */
    public function comprobarEvento(): bool
    {
        $sectores = $this->sectores()
            ->where('sectores.activo', true)
            ->get();

        $hayDisponibilidad = false;

        foreach ($sectores as $sector) {
            $sectorDisponible = $this->sectorTieneAsientosDisponibles($sector->id);
            $this->actualizarDisponibilidadSector($sector->id, $sectorDisponible);

            if ($sectorDisponible) {
                $hayDisponibilidad = true;
            }
        }

        return $hayDisponibilidad;
    }

    /**
     * Verificar si quedan asientos libres para un sector en este evento.
     */
    public function sectorTieneAsientosDisponibles($sectorId): bool
    {
        return Asiento::where('sector_id', $sectorId)
            ->whereDoesntHave('estadoAsientos', function ($query) {
                $query->where('evento_id', $this->id)
                      ->where(function ($query) {
                          $query->where('estado', 'OCUPADO')
                                ->orWhere(function ($query) {
                                    $query->where('estado', 'RESERVADO')
                                          ->where('reservado_hasta', '>', now());
                                });
                      });
            })
            ->exists();
    }

    /**
     * Un evento tiene muchos estados de asientos
     */
    public function estadoAsientos()
    {
        return $this->hasMany(EstadoAsiento::class);
    }

    /**
     * Un evento tiene muchas entradas vendidas
     */
    public function entradas()
    {
        return $this->hasMany(Entrada::class);
    }

    /**
     * Un evento tiene muchos artistas
     */
    public function artistas() // CON ESTO BORRO EN LA TABLA PIVOTE, NO EL ARTISTA EN SÍ
    {
        return $this->belongsToMany(Artista::class, 'artista_evento')
                    ->withTimestamps();
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    /**
     * Obtener sectores disponibles (activos y con disponible=true)
     */
    public function sectoresDisponibles()
    {
        return $this->sectores()
            ->select('sectores.*')
            ->where('sectores.activo', true)
            ->wherePivot('disponible', true);
    }

    /**
     * Obtener el precio de un sector específico
     */
    public function precioDelSector($sectorId)
    {
        return $this->precios()
            ->where('sector_id', $sectorId)
            ->first();
    }

    /**
     * Verificar si un sector está disponible para este evento
     */
    public function sectorEstaDisponible($sectorId): bool
    {
        return $this->precios()
            ->where('sector_id', $sectorId)
            ->where('disponible', true)
            ->exists();
    }

    /**
     * Obtener total de asientos disponibles
     */
    public function totalAsientosDisponibles(): int
    {
        $sectoresDisponibles = $this->sectoresDisponibles()->pluck('sectores.id');

        if ($sectoresDisponibles->isEmpty()) {
            return 0;
        }

        $totalAsientos = Asiento::whereIn('sector_id', $sectoresDisponibles)->count();

        $asientosNoDisponibles = $this->estadoAsientos()
            ->where(function ($query) {
                $query->where('estado', 'OCUPADO')
                    ->orWhere(function ($query) {
                        $query->where('estado', 'RESERVADO')
                            ->where(function ($query) {
                                $query->whereNull('reservado_hasta')
                                    ->orWhere('reservado_hasta', '>', now());
                            });
                    });
            })
            ->count();

        return max(0, $totalAsientos - $asientosNoDisponibles);
    }

    /**
     * Obtener total de entradas vendidas
     */
    public function totalEntradasVendidas(): int
    {
        return $this->entradas()->count();
    }

    /**
     * Verificar si el evento ya pasó
     */
    public function yaPaso(): bool
    {
        return $this->fecha->isPast();
    }

    /**
     * Verificar si el evento es hoy
     */
    public function esHoy(): bool
    {
        return $this->fecha->isToday();
    }

    /**
     * Scope: Solo eventos futuros
     */
    public function scopeFuturos($query)
    {
        return $query->where('fecha', '>=', now()->toDateString())
                     ->orderBy('fecha', 'asc');
    }

    /**
     * Scope: Solo eventos pasados
     */
    public function scopePasados($query)
    {
        return $query->where('fecha', '<', now()->toDateString())
                     ->orderBy('fecha', 'desc');
    }

    /**
     * Scope: Eventos de un mes específico
     */
    public function scopeDelMes($query, $mes, $anio)
    {
        return $query->whereMonth('fecha', $mes)
                     ->whereYear('fecha', $anio);
    }
}
