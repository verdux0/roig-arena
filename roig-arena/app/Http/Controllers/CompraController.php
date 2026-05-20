<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Asiento;
use App\Models\Sector;
use App\Models\Precio;
use Illuminate\Http\Request;
use App\Models\Entrada;
use App\Models\EstadoAsiento;
use App\Services\ReservaService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class CompraController extends Controller
{
    /**
     * Mostrar página de compra con datos del evento
     */
    public function show($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);
        $sectoresDisponibles = $this->obtenerSectoresDisponibles($eventoId)->getData()->data;
        return view('compra.buy', compact('evento', 'sectoresDisponibles'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'reservas' => 'required|array',
            'reservas.*' => 'exists:estado_asientos,id',
        ]);

        $user = auth()->user();

        $reservas = EstadoAsiento::whereIn('id', $request->reservas)->get();
        $evento = null;

        foreach ($reservas as $reserva) {
            // Expirada
            if ($reserva->reservado_hasta < now()) {
                return response()->json(['error' => 'Reserva expirada'], 400);
            }

            // No pertenece al usuario
            if ($reserva->user_id !== $user->id) {
                return response()->json(['error' => 'No autorizada'], 400);
            }

            // Crear entrada
            $precioPagado = (float) (Precio::where('evento_id', $reserva->evento_id)
                ->where('sector_id', $reserva->asiento->sector_id)
                ->value('precio') ?? 0);

            Entrada::create([
                'user_id' => $user->id,
                'evento_id' => $reserva->evento_id,
                'asiento_id' => $reserva->asiento_id,
                'precio_pagado' => $precioPagado,
                'codigo_qr' => Str::random(32),
            ]);

            // Marcar asiento como ocupado
            $reserva->update([
                'estado' => 'OCUPADO',
            ]);

            if (!$evento) {
                $evento = $reserva->evento;
            }
        }

        if ($evento) {
            $evento->comprobarEvento();
        }

        return response()->json([
            'success' => true,
            'message' => 'Compra procesada exitosamente',
        ], 201);
    }


    /**
     * API endpoint para traer asientos por sector (JSON)
     */
    public function obtenerAsientos($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);
        $sectores = $evento->sectores()->with('asientos')->get();

        return response()->json([
            'success' => true,
            'data' => $sectores
        ]);
    }

    /**
     * API endpoint filtrado por sector
     */
    public function obtenerAsientoDelSector($sectorId)
    {
        $sector = Sector::findOrFail($sectorId);
        $asientos = $sector->asientos()->where('disponible', true)->get();

        return response()->json([
            'success' => true,
            'data' => $asientos
        ]);
    }

    /**
     * Añadir asiento al carrito temporal
     */
    public function agregarAlCarrito(Request $request)
    {
        $request->validate([
            'asiento_id' => 'required|exists:asientos,id',
            'evento_id' => 'required|exists:eventos,id'
        ]);

        $carrito = session()->get('carrito', []);
        $asientoId = $request->asiento_id;

        if (!isset($carrito[$asientoId])) {
            $asiento = Asiento::find($asientoId);
            $carrito[$asientoId] = [
                'asiento_id' => $asientoId,
                'evento_id' => $request->evento_id,
                'numero' => $asiento->numero,
                'sector' => $asiento->sector->nombre,
                'precio' => $asiento->precio
            ];
        }

        session()->put('carrito', $carrito);

        return response()->json([
            'success' => true,
            'message' => 'Asiento añadido al carrito',
            'carrito' => $carrito
        ]);
    }

    /**
     * Remover asiento del carrito
     */
    public function removerDelCarrito(Request $request)
    {
        $request->validate([
            'asiento_id' => 'required'
        ]);

        $carrito = session()->get('carrito', []);
        $asientoId = $request->asiento_id;

        if (isset($carrito[$asientoId])) {
            unset($carrito[$asientoId]);
            session()->put('carrito', $carrito);
        }

        return response()->json([
            'success' => true,
            'message' => 'Asiento removido del carrito',
            'carrito' => $carrito
        ]);
    }

    /**
     * Obtener estado actual del carrito
     */
    public function obtenerCarrito()
    {
        $carrito = session()->get('carrito', []);
        $total = collect($carrito)->sum('precio');

        return response()->json([
            'success' => true,
            'carrito' => $carrito,
            'total' => $total,
            'cantidad' => count($carrito)
        ]);
    }

    /**
     * Obtener sectores disponibles para un evento
     */
    public function obtenerSectoresDisponibles($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);
        $sectoresDisponibles = $evento->sectores()
            ->activos()
            ->wherePivot('disponible', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sectoresDisponibles
        ]);
    }

    /**
     * Procesar compra final
     */
    public function confirmarCompra(Request $request)
    {
        $request->validate([
            'metodo_pago' => 'required|in:tarjeta,efectivo,transferencia'
        ]);

        $user = $request->user();

        try {
            $resultado = DB::transaction(function () use ($user) {
                $reservas = EstadoAsiento::with('asiento')
                    ->where('user_id', $user->id)
                    ->where('estado', 'RESERVADO')
                    ->where('reservado_hasta', '>', now())
                    ->lockForUpdate()
                    ->get();

                if ($reservas->isEmpty()) {
                    return null;
                }

                $total = 0;

                $evento = null;

                foreach ($reservas as $reserva) {
                    $precioAsiento = (float) (Precio::where('evento_id', $reserva->evento_id)
                        ->where('sector_id', $reserva->asiento->sector_id)
                        ->value('precio') ?? 0);
                    $total += $precioAsiento;

                    Entrada::create([
                        'user_id' => $user->id,
                        'evento_id' => $reserva->evento_id,
                        'asiento_id' => $reserva->asiento_id,
                        'precio_pagado' => $precioAsiento,
                        'codigo_qr' => Str::random(32),
                    ]);

                    $reserva->update([
                        'estado' => 'OCUPADO',
                    ]);

                    if (!$evento) {
                        $evento = $reserva->evento;
                    }
                }

                if ($evento) {
                    $evento->comprobarEvento();
                }

                return [
                    'total' => $total,
                    'cantidad_entradas' => $reservas->count(),
                ];
            });

            if ($resultado === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'El carrito está vacío'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Compra confirmada exitosamente',
                'total' => $resultado['total'],
                'cantidad_entradas' => $resultado['cantidad_entradas']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la compra: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar pagos pendientes del usuario
     */
    public function misPagosPendientes()
    {
        $user = auth()->user();

        // Obtener reservas activas del usuario agrupadas por evento
        $reservasPorPagar = EstadoAsiento::where('user_id', $user->id)
            ->where('estado', 'RESERVADO')
            ->where('reservado_hasta', '>', now())
            ->with(['evento', 'asiento.sector'])
            ->orderBy('reservado_hasta')
            ->get()
            ->groupBy('evento_id');

        // Transformar datos para la vista
        $pagosPendientes = collect();
        foreach ($reservasPorPagar as $eventoId => $reservas) {
            $evento = $reservas->first()->evento;
            $montoPendiente = 0;
            $reservasConPrecio = [];

            foreach ($reservas as $reserva) {
                $precio = Precio::where('evento_id', $eventoId)
                    ->where('sector_id', $reserva->asiento->sector_id)
                    ->value('precio') ?? 0;

                $montoPendiente += $precio;

                $reservasConPrecio[] = [
                    'id' => $reserva->id,
                    'asiento' => [
                        'fila' => $reserva->asiento->fila,
                        'numero' => $reserva->asiento->numero,
                        'sector' => [
                            'nombre' => $reserva->asiento->sector->nombre,
                        ],
                    ],
                    'precio_asiento' => (float) $precio,
                    'reservado_hasta' => optional($reserva->reservado_hasta)?->toIso8601String(),
                ];
            }

            $expiraTimestamp = $reservas->min(function ($reserva) {
                return optional($reserva->reservado_hasta)->timestamp;
            });

            $pagosPendientes->push([
                'evento' => $evento,
                'reservas' => $reservasConPrecio,
                'total' => count($reservasConPrecio),
                'montoPendiente' => $montoPendiente,
                'reservado_hasta' => $expiraTimestamp
                    ? Carbon::createFromTimestamp($expiraTimestamp)->toIso8601String()
                    : null,
            ]);
        }

        return view('compra.pagos-pendientes', compact('pagosPendientes'));
    }

    /**
     * Procesar pago de entradas pendientes (API endpoint)
     */
    public function procesarPagoPendiente(Request $request)
    {
        $request->validate([
            'reservas_ids' => 'required|array',
            'reservas_ids.*' => 'exists:estado_asientos,id',
            'metodo_pago' => 'required|in:tarjeta,efectivo,transferencia'
        ]);

        $user = $request->user();
        $reservasIds = $request->reservas_ids;

        try {
            $resultado = DB::transaction(function () use ($user, $reservasIds) {
                $reservas = EstadoAsiento::whereIn('id', $reservasIds)
                    ->where('user_id', $user->id)
                    ->where('estado', 'RESERVADO')
                    ->where('reservado_hasta', '>', now())
                    ->with('asiento')
                    ->lockForUpdate()
                    ->get();

                if ($reservas->count() !== count($reservasIds)) {
                    return null;
                }

                $total = 0;

                $evento = null;

                foreach ($reservas as $reserva) {
                    $precio = (float) (Precio::where('evento_id', $reserva->evento_id)
                        ->where('sector_id', $reserva->asiento->sector_id)
                        ->value('precio') ?? 0);

                    Entrada::create([
                        'user_id' => $user->id,
                        'evento_id' => $reserva->evento_id,
                        'asiento_id' => $reserva->asiento_id,
                        'precio_pagado' => $precio,
                        'codigo_qr' => Str::random(32),
                    ]);

                    $reserva->update([
                        'estado' => 'OCUPADO',
                        'reservado_hasta' => null,
                    ]);

                    if (!$evento) {
                        $evento = $reserva->evento;
                    }

                    $total += $precio;
                }

                if ($evento) {
                    $evento->comprobarEvento();
                }

                return [
                    'cantidad' => $reservas->count(),
                    'total' => $total,
                ];
            });

            if ($resultado === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hay reservas expiradas o no autorizadas. Recarga la página.'
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pago procesado exitosamente',
                'cantidad' => $resultado['cantidad'],
                'total' => $resultado['total']
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancelar reservas pendientes desde la vista web.
     */
    public function cancelarPagoPendiente(Request $request, ReservaService $service)
    {
        $request->validate([
            'reservas_ids' => 'required|array',
            'reservas_ids.*' => 'exists:estado_asientos,id',
        ]);

        $user = $request->user();

        try {
            foreach ($request->reservas_ids as $reservaId) {
                $service->cancelarReserva($reservaId, $user->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reservas canceladas correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se han podido cancelar las reservas: ' . $e->getMessage(),
            ], 409);
        }
    }
}
