<?php

namespace App\Http\Controllers;

use App\Models\Artista;
use App\Models\Evento;
use App\Models\Precio;
use App\Models\Sector;
use App\Models\Asiento;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventoController extends Controller
{
    /**
     * Listar eventos futuros (público)
     */
    public function index()
    {
        $eventos = Evento::futuros()
            ->with(['precios.sector'])
            ->get();

        return response()->json([
            'data' => $eventos,
        ]);
    }

    /**
     * Ver detalle de un evento (público)
     */
    public function show($id)
    {
        $evento = Evento::with(['precios.sector'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'asientos_disponibles' => $evento->totalAsientosDisponibles(),
                'entradas_vendidas' => $evento->totalEntradasVendidas(),
                'evento' => $evento,
                'sectores_disponibles' => $evento->sectoresDisponibles()->get(),
            ],
        ]);
    }

    /**
     * Mostrar formulario de creación de evento (admin)
     */
    public function create()
    {
        $sectoresDisponibles = Sector::activos()->get();

        return view('eventos.create', compact('sectoresDisponibles'));
    }

    /**
     * Mostrar el editor visual de sectores de un evento.
     */
    public function sectorEditor($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);
        $sectoresIniciales = Sector::activos()->get();

        return view('eventos.sectores-editor', [
            'evento' => $evento,
            'eventoId' => $evento->id,
            'sectoresIniciales' => $sectoresIniciales,
        ]);
    }

    /**
     * Crear evento (admin)
     *
     * Flujo:
     * 1. Valida todos los campos del formulario (evento + sectores + precios)
     * 2. Crea el evento en BD con datos básicos (nombre, fecha, hora, etc)
     * 3. Construye array de precios vinculando cada sector elegido con su precio
     * 4. Guarda la relación evento-sector-precio en tabla 'precios'
     * 5. Retorna JSON (si es API) o redirige con mensaje de éxito
     */
    public function store(Request $request)
    {
        // PASO 1: Validar datos básicos del evento
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion_corta' => 'required|string|max:255',
            'descripcion_larga' => 'required|string',
            'poster_url' => 'nullable|url',
            'poster_ancho_url' => 'nullable|url',
            'fecha' => 'required|date|unique:eventos,fecha',
            'hora' => 'required|date_format:H:i',
        ]);

        // PASO 2: Validar sectores/precios.
        // Para el formulario web son obligatorios. Para la API, son opcionales,
        // pero si se envían deben ser válidos.
        $sectores = $request->input('sectores', []);
        $precios = $request->input('precios', []);

        $sectorRules = [
            'sectores' => 'array|min:1',
            'sectores.*' => [
                'required',
                'integer',
                Rule::exists('sectores', 'id')->where('activo', true),
            ],
            'precios' => 'array|min:1',
            'precios.*' => 'required|numeric|min:0',
        ];

        if (!$request->expectsJson() && !$request->is('api/*')) {
            $sectorRules['sectores'] = 'required|array|min:1';
            $sectorRules['precios'] = 'required|array|min:1';
        }

        if ($request->has('sectores') || $request->has('precios') || (!$request->expectsJson() && !$request->is('api/*'))) {
            $request->validate($sectorRules);
        }

        // PASO 3: Crear el evento en BD con los datos validados
        // Solo guarda campos del evento, NO los sectores/precios aún
        $evento = Evento::create($validated);

        // PASO 4: Construir array de datos para tabla 'precios'
        // Cada entrada relaciona: evento_id + sector_id + precio + disponible
        // El orden de los arrays es importante: sector[0] con precio[0], etc.
        $precioData = [];
        foreach ($sectores as $index => $sectorId) {
            $precioData[] = [
                'sector_id' => $sectorId,                      // ID del sector del estadio
                'precio' => $precios[$index] ?? 0,             // Precio para este evento
                'disponible' => true,                          // Por defecto disponible en venta
            ];
        }

        // PASO 5: Guardar todos los precios en BD (relación evento-sector-precio)
        // createMany() inserta varias filas a la vez con evento_id automático
        $evento->precios()->createMany($precioData);

        // PASO 6: Retornar respuesta según el tipo de request
        // Si es llamada API (JSON): responder con datos del evento creado
        // Si es formulario web: redirigir al formulario con mensaje de éxito
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => $evento,
                'message' => 'Evento creado correctamente',
            ], 201);
        }

        return redirect(route('eventos.show', ['evento' => $evento->id], false))
            ->with('success', 'Evento creado correctamente. Ya está disponible en el catálogo.');
    }

    /**
     * Actualizar evento (admin)
     */
    public function update(Request $request, $id)
    {
        $evento = Evento::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'descripcion_corta' => 'sometimes|string|max:255',
            'descripcion_larga' => 'sometimes|string',
            'poster_url' => 'nullable|url',
            'poster_ancho_url' => 'nullable|url',
            'fecha' => 'sometimes|date|unique:eventos,fecha,' . $id,
            'hora' => 'sometimes|date_format:H:i',
        ]);

        $evento->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'data' => $evento,
                'message' => 'Evento actualizado correctamente',
            ]);
        }

        return back()->with('success', 'Evento actualizado correctamente.');
    }

    /**
     * Eliminar evento (admin)
     */
    public function destroy(Request $request, $id)
    {
        $evento = Evento::findOrFail($id);

        // Verificar que no tenga entradas vendidas
        if ($evento->totalEntradasVendidas() > 0) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'error' => 'No se puede eliminar un evento con entradas vendidas',
                ], 400);
            } else {
                return back()->with('error', 'No se puede eliminar un evento con entradas vendidas');
            }
        }

        $evento->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Evento eliminado correctamente',
            ]);
        } else {
            return back()->with('success', 'Evento eliminado correctamente');
        }
    }

    /**
     * Quitar un artista de un evento (admin).
     */
    public function detachArtista(Request $request, $eventoId, $artistaId)
    {
        $evento = Evento::findOrFail($eventoId);
        Artista::findOrFail($artistaId);

        $evento->artistas()->detach($artistaId);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Artista quitado del evento correctamente',
            ]);
        }

        return back()->with('success', 'Artista quitado del evento correctamente.');
    }

    /**
     * Listar eventos del usuario autenticado (público)
     */
    public function misEventos()
    {
        $miseventos = Evento::whereHas('entradas', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with(['precios.sector'])
            ->orderBy('fecha', 'asc')
            ->paginate(9);

        return view('auth.mis-eventos', [
            'miseventos' => $miseventos,
        ]);
    }

    /**
     * Información detallada de eventos del usuario autenticado (público)
     */
    public function misEventosInfo()
    {
        $miseventos = Evento::whereHas('entradas', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with([
                'precios.sector',
                'artistas',
                'entradas' => function ($q) {
                    $q->where('user_id', auth()->id());
                },
                'entradas.asiento.sector'
            ])
            ->orderBy('fecha', 'asc')
            ->get();

        return view('auth.mis-eventos-info', [
            'miseventos' => $miseventos,
        ]);
    }

    /*
    * Información detallada de un evento del usuario autenticado (público)
     */
    public function miEventoInfo($id)
    {
        $evento = Evento::whereHas('entradas', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->with([
                'precios.sector',
                'artistas',
                'entradas' => function ($q) use ($id) {
                    $q->where('user_id', auth()->id())->where('evento_id', $id);
                },
                'entradas.asiento.sector'
            ])
            ->findOrFail($id);

        return view('auth.mi-evento-info', [
            'evento' => $evento,
        ]);
    }

    /**
     * Deshabilitar sector de un evento (admin) (borra de la tabla precios, no de sectores.)
     */
    public function disableSector($id) {
        $precio = Precio::findOrFail($id);
        $precio->delete();

        return back()->with('success', 'Sector deshabilitado correctamente para este evento.');
    }

    /**
     * Actualizar el precio de un sector de un evento (admin)
     */
    public function updateSectorPrice(Request $request, $id)
    {
        $precio = Precio::findOrFail($id);

        $validated = $request->validate([
            'precio' => 'required|numeric|min:0',
        ]);

        $precio->update($validated);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'data' => $precio->load('sector'),
                'message' => 'Precio actualizado correctamente',
            ]);
        }

        return back()->with('success', 'Precio actualizado correctamente.');
    }

    /**
     * Borrado masivo de precios (admin)
     * Espera un array `ids` con los ids de precio a eliminar.
     */
    public function bulkDeletePrecios(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:precios,id',
        ]);

        $ids = $validated['ids'];

        Precio::whereIn('id', $ids)->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'deleted' => $ids,
                'message' => 'Precios eliminados correctamente',
            ]);
        }

        return back()->with('success', 'Precios eliminados correctamente.');
    }

    /**
     * Añadir (asociar) un artista a un evento (admin).
     */
    public function attachArtista(Request $request, $eventoId)
    {
        $request->validate([
            'artista_id' => 'required|exists:artistas,id',
        ]);

        $evento = Evento::findOrFail($eventoId);
        $artistaId = $request->input('artista_id');

        // Evita duplicados
        $evento->artistas()->syncWithoutDetaching([$artistaId]);

        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'message' => 'Artista añadido al evento correctamente',
                'artista_id' => $artistaId,
            ]);
        }

        return back()->with('success', 'Artista añadido al evento correctamente.');
    }

    /**
     * Añadir sector a un evento (admin)
     */
    public function attachSector(Request $request, $eventoId)
    {
        $request->validate([
            'sector_id' => 'required|exists:sectores,id',
            'precio' => 'required|numeric|min:0',
        ]);

        $evento = Evento::findOrFail($eventoId);
        $sectorId = $request->input('sector_id');

        // Crea o actualiza el precio para el sector en este evento
        $precio = Precio::updateOrCreate(
            [
                'evento_id' => $eventoId,
                'sector_id' => $sectorId,
            ],
            [
                'precio' => $request->input('precio'),
                'disponible' => true,
            ]
        );

        $precio->load('sector');

        if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
            return response()->json([
                'message' => 'Sector añadido al evento correctamente',
                'data' => $precio,
            ]);
        }

        return back()->with('success', 'Sector añadido al evento correctamente.');
    }

    /**
     * Quitar sector de un evento (admin)
     */
    public function detachSector(Request $request, $eventoId, $sectorId)
    {
        $evento = Evento::findOrFail($eventoId);
        Sector::findOrFail($sectorId);

        // Elimina la entrada en 'precios' que vincula el evento con el sector
        Precio::where('evento_id', $eventoId)
            ->where('sector_id', $sectorId)
            ->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Sector quitado del evento correctamente',
            ]);
        }

        return back()->with('success', 'Sector quitado del evento correctamente.');
    }

    public function mostrarTodosLosAsientos(Evento $evento)
{
        // SELECT asientos con relación a estado_asientos del evento
        $asientos = Asiento::whereHas('sector', function ($query) use ($evento) {
            $query->whereIn('id', $evento->sectores()->pluck('id'));
        })
        ->with(['sector', 'estadoAsientos' => function ($query) use ($evento) {
            $query->where('evento_id', $evento->id);
        }])
        ->get()
        ->map(function ($asiento) use ($evento) {
            $estado = $asiento->estadoAsientos->firstWhere('evento_id', $evento->id);
            $disponible = !$estado || 
                        ($estado->estado !== 'reservado' && $estado->estado !== 'vendido');
            
            return [
                'id' => $asiento->id,
                'fila' => $asiento->fila,
                'numero' => $asiento->numero,
                'sector_id' => $asiento->sector_id,
                'sector_nombre' => $asiento->sector->nombre,
                'disponible' => $disponible,
                'estado' => $disponible ? 'disponible' : 'ocupado'
            ];
        });

        return response()->json([
            'data' => [
                'total_filas' => 12,    // Obtener del evento o constante
                'total_columnas' => 20,  // Obtener del evento o constante
                'asientos' => $asientos
            ]
        ]);
    }
}
