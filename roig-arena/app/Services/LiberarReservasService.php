<?php

namespace App\Services;

use App\Models\EstadoAsiento;
use Illuminate\Support\Facades\Log;

class LiberarReservasService
{
    /**
     * Liberar todas las reservas expiradas
     */
    public function liberarExpiradas()
    {
        $expiradas = EstadoAsiento::expirados()->get();
        
        $count = 0;
        
        foreach ($expiradas as $reserva) {
            $reserva->delete();
            $count++;
            
            Log::info('Reserva expirada liberada', [
                'reserva_id' => $reserva->id,
                'evento_id' => $reserva->evento_id,
                'asiento_id' => $reserva->asiento_id,
            ]);
        }
        
        return $count;
    }

    /**
     * Liberar reservas de un usuario específico
     */
    public function liberarPorUsuario($userId)
    {
        $expiradas = EstadoAsiento::expirados()
            ->where('user_id', $userId)
            ->get();
        
        foreach ($expiradas as $reserva) {
            $reserva->delete();
        }
        
        return $expiradas->count();
    }
}