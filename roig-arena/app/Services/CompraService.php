<?php

namespace App\Services;

use App\Models\EstadoAsiento;
use App\Models\Entrada;
use Illuminate\Support\Facades\DB;

class CompraService
{
    /**
     * Procesar compra de múltiples reservas
     */
    public function procesarCompra(array $reservasIds, $userId)
    {
        $entradas = [];

        DB::beginTransaction();
        try {
            foreach ($reservasIds as $reservaId) {
                $reserva = $this->obtenerReserva($reservaId, $userId);

                // Verificar expiración
                $this->verificarNoExpirada($reserva);

                // Obtener precio
                $precio = $this->obtenerPrecio($reserva);

                // Marcar como vendido
                $reserva->marcarComoVendido();

                // Crear entrada y cargar relaciones
                $entrada = $this->crearEntrada($reserva, $precio, $userId);
                $entrada->load(['evento', 'asiento.sector']);

                $entradas[] = $entrada;
            }
            DB::commit();

            return collect($entradas);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener una reserva del usuario
     */
    private function obtenerReserva($reservaId, $userId)
    {
        return EstadoAsiento::where('id', $reservaId)
            ->where('user_id', $userId)
            ->where('estado', 'RESERVADO')
            ->with(['evento', 'asiento.sector'])
            ->firstOrFail();
    }

    /**
     * Verificar que la reserva no haya expirado
     */
    private function verificarNoExpirada($reserva)
    {
        if ($reserva->haExpirado()) {
            throw new \Exception('Una de las reservas ha expirado');
        }
    }

    /**
     * Obtener el precio del sector para el evento
     */
    private function obtenerPrecio($reserva)
    {
        $precio = $reserva->evento->precioDelSector($reserva->asiento->sector_id);

        if (!$precio) {
            throw new \Exception('No se encontró el precio para el sector');
        }

        return $precio;
    }

    /**
     * Crear la entrada
     */
    private function crearEntrada($reserva, $precio, $userId)
    {
        return Entrada::create([
            'user_id' => $userId,
            'evento_id' => $reserva->evento_id,
            'asiento_id' => $reserva->asiento_id,
            'precio_pagado' => $precio->precio,
            'codigo_qr' => Entrada::generarCodigoQR(),
        ]);
    }
}
