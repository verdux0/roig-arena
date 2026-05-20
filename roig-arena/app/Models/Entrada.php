<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Entrada extends Model
{
    use HasFactory;

    protected $table = 'entradas';

    protected $fillable = [
        'user_id',
        'evento_id',
        'asiento_id',
        'precio_pagado',
        'codigo_qr',
        'descargada',
        'utilizada',
    ];

    /**
     * Casteo de tipos
     */
    protected $casts = [
        'precio_pagado' => 'decimal:2',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    /**
     * Una entrada pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Una entrada pertenece a un evento
     */
    public function evento()
    {
        return $this->belongsTo(Evento::class);
    }

    /**
     * Una entrada pertenece a un asiento
     */
    public function asiento()
    {
        return $this->belongsTo(Asiento::class);
    }

    // ============================================
    // MÉTODOS ÚTILES
    // ============================================

    /**
     * Generar código QR único
     */
    public static function generarCodigoQR(): string
    {
        do {
            $codigo = 'QR-' . strtoupper(Str::random(29));
        } while (self::where('codigo_qr', $codigo)->exists());

        return $codigo;
    }

    /**
     * Obtener precio formateado
     */
    public function precioFormateado(): string
    {
        return number_format($this->precio_pagado, 2, ',', '.') . ' €';
    }

    /**
     * Obtener información completa de la entrada
     */
    public function informacionCompleta(): array
    {
        return [
            'codigo_qr' => $this->codigo_qr,
            'evento' => $this->evento->nombre,
            'fecha' => $this->evento->fecha->format('d/m/Y'),
            'hora' => $this->evento->hora,
            'asiento' => $this->asiento->nombreCompleto(),
            'precio' => $this->precioFormateado(),
            'comprador' => $this->user->nombre . ' ' . $this->user->apellido,
        ];
    }

    /**
     * Verificar si la entrada es válida (evento no pasó)
     */
    public function esValida(): bool
    {
        return !$this->evento->yaPaso();
    }

    /**
     * Scope: Entradas de un usuario
     */
    public function scopeDeUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Entradas de un evento
     */
    public function scopeDeEvento($query, $eventoId)
    {
        return $query->where('evento_id', $eventoId);
    }

    /**
     * Scope: Entradas válidas (eventos futuros)
     */
    public function scopeValidas($query)
    {
        return $query->whereHas('evento', function ($q) {
            $q->where('fecha', '>=', now()->toDateString());
        });
    }

    /**
     * Boot del modelo - Generar código QR automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entrada) {
            if (!$entrada->codigo_qr) {
                $entrada->codigo_qr = self::generarCodigoQR();
            }
        });
    }
}
