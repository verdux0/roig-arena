<?php

namespace App\Http\Controllers;

use App\Models\Artista;
use App\Models\Evento;
use App\Http\Resources\ArtistaResource;
use Illuminate\Http\Request;

class ArtistaController extends Controller
{
    /**
     * Listar todos los artistas
     */
    public function index()
    {
        $artistas = Artista::with('eventos')->get();

        return response()->json([
            'data' => ArtistaResource::collection($artistas),
        ]);
    }

    /**
     * Listar artistas por evento
     */
    public function porEvento($eventoId)
    {
        $evento = Evento::findOrFail($eventoId);
        $artistas = $evento->artistas()->get();

        return response()->json([
            'data' => ArtistaResource::collection($artistas),
        ]);
    }

    /**
     * Ver detalle de un artista
     */
    public function show($id)
    {
        $artista = Artista::with('eventos')->findOrFail($id);

        return response()->json([
            'data' => new ArtistaResource($artista),
        ]);
    }

    /**
     * Crear artista (admin)
     */
    public function create()
    {
        return view('artistas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'imagen_url' => 'nullable|url',
        ]);

        // Crear artista en catálogo
        $artista = Artista::create($request->only(['nombre', 'descripcion', 'imagen_url']));

        // Asociar al evento indicado
        if ($request->filled('evento_id')) {
            $artista->eventos()->attach($request->input('evento_id'));
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => new ArtistaResource($artista),
                'message' => 'Artista creado correctamente',
            ], 201);
        }

        return redirect()->route('admin.artistas.create')->with('success', 'Artista creado correctamente.');
    }

    /**
     * Actualizar artista (admin)
     */
    public function update(Request $request, $id)
    {
        $artista = Artista::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'evento_id' => 'sometimes|exists:eventos,id',
            'descripcion' => 'nullable|string',
            'imagen_url' => 'nullable|url',
        ]);

        $artista->update($request->only(['nombre', 'descripcion', 'imagen_url']));

        // Si llega evento_id, sincronizar asociación con ese evento (añadir si falta)
        if ($request->has('evento_id')) {
            $eventoId = $request->input('evento_id');
            if ($eventoId) {
                $artista->eventos()->syncWithoutDetaching([$eventoId]);
            }
        }

        return response()->json([
            'data' => new ArtistaResource($artista),
            'message' => 'Artista actualizado correctamente',
        ]);
    }

    /**
     * Eliminar artista (admin)
     */
    public function destroy($id)
    {
        $artista = Artista::findOrFail($id);
        $artista->eventos()->detach();
        $artista->delete();

        return response()->json([
            'message' => 'Artista eliminado correctamente',
        ]);
    }

    /**
     * Buscar artista por nombre
     */
    public function buscar(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([
                'error' => 'El parámetro "q" es requerido',
            ], 400);
        }

        $artistas = Artista::where('nombre', 'like', "%{$query}%")
            ->with('eventos')
            ->get();

        return response()->json([
            'data' => ArtistaResource::collection($artistas),
        ]);
    }
}
