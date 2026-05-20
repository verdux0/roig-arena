<?php

// REVISAR: Este recurso no se está utilizando actualmente, pero se ha creado para futuras implementaciones de la API de compras. Se puede eliminar si no se va a usar.

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evento' => new EventoResource($this->evento),
            'asientos' => AsientoResource::collection($this->asientos),
            'precio_total' => number_format($this->precio_total, 2, ',', '.') . ' €',
            'metodo_pago' => $this->metodo_pago,
            'fecha_compra' => $this->created_at->format('d/m/Y H:i'),
        ];
    }
}