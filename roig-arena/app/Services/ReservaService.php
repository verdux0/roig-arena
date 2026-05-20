<?php

namespace App\Services;

use App\Models\EstadoAsiento;
use App\Models\Asiento;
use App\Models\Evento;
use Illuminate\Support\Facades\DB;

class ReservaService
{
    /**
     * Reservar un asiento para un evento
     */
    public function reservarAsiento($eventoId, $asientoId, $userId)
    {
        DB::beginTransaction();
        try {
            // Bloqueo pesimista: evita race condition sobre el estado actual del asiento.
            $estadoAsiento = EstadoAsiento::where('evento_id', $eventoId)
                ->where('asiento_id', $asientoId)
                ->lockForUpdate()
                ->first();

            $asiento = Asiento::findOrFail($asientoId);
            $evento = Evento::findOrFail($eventoId);

            $this->verificarSectorDisponible($evento, $asiento->sector_id);

            if (!$estadoAsiento) {
                $reserva = EstadoAsiento::create([
                    'evento_id' => $eventoId,
                    'asiento_id' => $asientoId,
                    'user_id' => $userId,
                    'estado' => 'RESERVADO',
                    'reservado_hasta' => now()->addMinutes(15),
                ]);
            } elseif ($estadoAsiento->estado === 'DISPONIBLE') {
                $estadoAsiento->update([
                    'user_id' => $userId,
                    'estado' => 'RESERVADO',
                    'reservado_hasta' => now()->addMinutes(15),
                ]);
                $reserva = $estadoAsiento->fresh();
            } elseif ($estadoAsiento->estado === 'RESERVADO') {
                $reservaExpirada = $estadoAsiento->reservado_hasta && $estadoAsiento->reservado_hasta->isPast();
                $reservaDelMismoUsuario = (int) $estadoAsiento->user_id === (int) $userId;

                if ($reservaExpirada || $reservaDelMismoUsuario) {
                    $estadoAsiento->update([
                        'user_id' => $userId,
                        'estado' => 'RESERVADO',
                        'reservado_hasta' => now()->addMinutes(15),
                    ]);
                    $reserva = $estadoAsiento->fresh();
                } else {
                    throw new \Exception('El asiento no está disponible');
                }
            } else {
                throw new \Exception('El asiento no está disponible');
            }

            DB::commit();

            return $reserva->load(['evento', 'asiento.sector']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Cancelar una reserva
     */
    public function cancelarReserva($reservaId, $userId)
    {
        $reserva = EstadoAsiento::where('id', $reservaId)
            ->where('user_id', $userId)
            ->where('estado', 'RESERVADO')
            ->firstOrFail();

        $reserva->delete();

        return true;
    }

    /**
     * Obtener reservas activas de un usuario
     */
    public function obtenerReservasActivas($userId)
    {
        return EstadoAsiento::where('user_id', $userId)
            ->where('estado', 'RESERVADO')
            ->where('reservado_hasta', '>', now())
            ->with(['evento', 'asiento.sector'])
            ->get();
    }

    /**
     * Verificar que el sector esté disponible para el evento
     */
    private function verificarSectorDisponible($evento, $sectorId)
    {
        if (!$evento->sectorEstaDisponible($sectorId)) {
            throw new \Exception('El sector no está disponible para este evento');
        }
    }
}
