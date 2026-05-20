<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoAsiento extends Model
{
    use HasFactory;

    protected $table = 'estado_asientos';

    // Constantes de estado
    public const DISPONIBLE = 1;
    public const RESERVADO = 2;
    public const OCUPADO = 3;

    protected $fillable = [
        'evento_id',
        'asiento_id',
        'user_id',
        'estado',
        'reservado_hasta',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'reservado_hasta' => 'datetime',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Un estado pertenece a un evento
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    /**
     * Un estado pertenece a un asiento
     */
    public function asiento()
    {
        return $this->belongsTo(Asiento::class);
    }

    /**
     * Un estado pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    /**
     * Verificar si la reserva ha expirado
     */
    public function haExpirado(): bool
    {
        if ($this->estado === 'OCUPADO') {
            return false; // Las ventas no expiran
        }

        return $this->reservado_hasta && $this->reservado_hasta->isPast();
    }

    public function estaDisponible(): bool
    {
        if ($this->estado === 'DISPONIBLE') {
            return true;
        }
        return false;
    }

     /**
     * Verificar si está RESERVADO (en carrito)
     */

    /**
     * Verificar si está RESERVADO (en carrito)
     */
    public function estaReservado(): bool
    {
        if ($this->estado === 'RESERVADO') {
            return true; // Las ventas no están reservadas
        }

        return false;
    }

    /**
     * Verificar si está OCUPADO (vendido)
     */
    public function estaOcupado(): bool
    {
        if ($this->estado === 'OCUPADO') {
            return true;
        }
        return false;
    }

    /**
     * Obtener tiempo restante de la reserva en minutos
     */
    public function tiempoRestante(): ?int
    {
        if ($this->estado === 'OCUPADO' || !$this->reservado_hasta) {
            return null;
        }

        $diff = now()->diffInMinutes($this->reservado_hasta, false);
        return $diff > 0 ? $diff : 0;
    }

    /**
     * Liberar el asiento (eliminar la reserva)
     */
    public function liberar(): bool
    {
        return $this->delete();
    }

    /**
     * Marcar como vendido
     */
    public function marcarComoVendido(): bool
    {
        $this->estado = 'OCUPADO';
        $this->reservado_hasta = null;
        return $this->save();
    }

    /**
     * Scope: Solo reservas bloqueadas
     */
    public function scopeBloqueados($query)
    {
        return $query->where('estado', 'RESERVADO');
    }

    /**
     * Scope: Solo ventas
     */
    public function scopeVendidos($query)
    {
        return $query->where('estado', 'OCUPADO');
    }

    /**
     * Scope: Reservas expiradas
     */
    public function scopeExpirados($query)
    {
        return $query->where('estado', 'RESERVADO')
                     ->where('reservado_hasta', '<', now());
    }

    /**
     * Scope: Reservas de un usuario
     */
    public function scopeDeUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Estados de un evento
     */
    public function scopeDeEvento($query, $eventoId)
    {
        return $query->where('evento_id', $eventoId);
    }
}
