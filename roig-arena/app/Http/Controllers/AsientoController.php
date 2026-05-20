<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Sector;
use App\Models\Asiento;
use Illuminate\Http\Request;

class AsientoController extends Controller
{
    /**
     * Obtener TODOS los asientos del evento con su estado y sector
     * 
     * Respuesta:
     * {
     *   "data": {
     *     "total_filas": 12,
     *     "total_columnas": 20,
     *     "asientos": [{id, fila, numero, disponible, estado, sector_id, sector_nombre}, ...]
     *   }
     * }
     */
    public function porEvento($eventoId) // sin utilizar creo REVISAR
    {
        $evento = Evento::findOrFail($eventoId);

        // Obtener sectores disponibles del evento
        $sectoresDisponibles = $evento->sectoresDisponibles()->get();
        $sectoresDisponiblesIds = $sectoresDisponibles->pluck('id');

        // Obtener TODOS los asientos de esos sectores
        $asientos = Asiento::whereIn('sector_id', $sectoresDisponiblesIds)
            ->with('sector')
            ->orderBy('sector_id')
            ->orderBy('fila')
            ->orderBy('numero')
            ->get()
            ->map(function ($asiento) use ($eventoId) {
                $disponible = $asiento->estaDisponible($eventoId);
                
                return [
                    'id' => $asiento->id,
                    'fila' => $asiento->fila,
                    'numero' => $asiento->numero,
                    'disponible' => $disponible,
                    'estado' => $disponible ? 'disponible' : 'ocupado',
                    'sector_id' => $asiento->sector_id,
                    'sector_nombre' => $asiento->sector->nombre,
                ];
            });

        // Calcular dimensiones del estadio (12 filas x 20 columnas es estándar)
        $totalFilas = $evento->sectores()
            ->pluck('fila_fin')
            ->max() ?? 12;
        
        $totalColumnas = $evento->sectores()
            ->pluck('columna_fin')
            ->max() ?? 20;

        return response()->json([
            'data' => [
                'total_filas' => (int)$totalFilas,
                'total_columnas' => (int)$totalColumnas,
                'asientos' => $asientos,
            ],
        ]);
    }

    /**
     * Obtener asientos de un sector específico para un evento
     */
    public function porSector($eventoId, $sectorId)
    {
        $evento = Evento::findOrFail($eventoId);
        $sector = Sector::findOrFail($sectorId);

        // Verificar que el sector esté disponible para el evento
        if (!$evento->sectorEstaDisponible($sectorId)) {
            return response()->json([
                'error' => 'El sector no está disponible para este evento',
            ], 400);
        }

        $asientos = $sector->asientos()
            ->get()
            ->map(function ($asiento) use ($eventoId) {
                return [
                    'id' => $asiento->id,
                    'fila' => $asiento->fila,
                    'numero' => $asiento->numero,
                    'disponible' => $asiento->estaDisponible($eventoId),
                ];
            });

        $precio = $evento->precioDelSector($sectorId);

        return response()->json([
            'data' => [
                'sector' => $sector,
                'precio' => $precio?->precio,
                'asientos' => $asientos,
            ],
        ]);
    }
}
