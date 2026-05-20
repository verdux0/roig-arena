<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion_corta' => $this->descripcion_corta,
            'descripcion_larga' => $this->when($request->routeIs('eventos.show'), $this->descripcion_larga),
            'poster' => $this->poster_url,
            'poster_ancho' => $this->poster_ancho_url,
            'fecha' => $this->fecha->format('d/m/Y'),
            'hora' => $this->hora ? $this->hora->format('H:i') : null,
            'sectores' => SectorResource::collection($this->whenLoaded('sectores')),
            'precios' => PrecioResource::collection($this->whenLoaded('precios')),
            'asientos_disponibles' => $this->when(
                $request->routeIs('eventos.show'),
                fn() => $this->totalAsientosDisponibles()
            ),
            'entradas_vendidas' => $this->when(
                $request->routeIs('eventos.show'),
                fn() => $this->totalEntradasVendidas()
            ),
        ];
    }
}