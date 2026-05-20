<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use Illuminate\Http\Request;

class EntradaController extends Controller
{
    /**
     * Listar mis entradas
     */
    public function index(Request $request)
    {
        $entradas = $request->user()
            ->entradas()
            ->with(['evento', 'asiento.sector'])
            ->latest()
            ->get()
            ->map(function ($entrada) {
                return [
                    'id' => $entrada->id,
                    'codigo_qr' => $entrada->codigo_qr,
                    'evento' => $entrada->evento->nombre,
                    'fecha' => $entrada->evento->fecha->format('d/m/Y'),
                    'hora' => $entrada->evento->hora,
                    'asiento' => $entrada->asiento->nombreCompleto(),
                    'precio' => $entrada->precioFormateado(),
                    'valida' => $entrada->esValida(),
                ];
            });

        return response()->json([
            'data' => $entradas,
        ]);
    }

    /**
     * Ver detalle de una entrada
     */
    public function show($id)
    {
        $entrada = Entrada::where('id', $id)
            ->where('user_id', auth()->id())
            ->with(['evento', 'asiento.sector'])
            ->firstOrFail();

        return response()->json([
            'data' => $entrada->informacionCompleta(),
        ]);
    }

    public function store(Request $request)
    {
        // Este método se encargaría de crear una nueva entrada después de la compra
        // La lógica de compra y reserva de asiento se manejaría en otro controlador (e.g. CompraController)
        $this->validate($request, [
            'evento_id' => 'required|exists:eventos,id',
            'asiento_id' => 'required|exists:asientos,id',
            'precio' => 'required|numeric|min:0',
        ]);

        $entrada = Entrada::create($request->all());

        return response()->json([
            'data' => $entrada,
            'message' => 'Entrada creada correctamente',
        ], 201);
    }

    /**
     * Marcar una entrada como descargada
     */
    public function marcarDescargada($id)
    {
        $entrada = Entrada::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $entrada->update(['descargada' => true]);

        return response()->json([
            'data' => $entrada,
            'message' => 'Entrada marcada como descargada. Ya no podrás cancelar esta compra.',
        ]);
    }

    /**
     * Cancelar una compra de entrada (solo si no está descargada)
     */
    public function cancelarCompra($id)
    {
        $entrada = Entrada::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($entrada->descargada) {
            return response()->json([
                'message' => 'No se puede cancelar una entrada ya descargada.',
            ], 409);
        }

        // Liberar el asiento
        $estadoAsiento = $entrada->asiento->estadoAsientos()
            ->where('evento_id', $entrada->evento_id)
            ->where('estado', 'OCUPADO')
            ->latest('id')
            ->first();

        if ($estadoAsiento) {
            $estadoAsiento->update([
                'estado' => 'DISPONIBLE',
                'user_id' => null,
                'reservado_hasta' => null,
            ]);
        }

        $entrada->delete();

        return response()->json([
            'message' => 'Compra cancelada correctamente.',
        ]);
    }
}
