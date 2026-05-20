<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
            'total_asientos' => $this->when(
                $this->relationLoaded('asientos'),
                fn() => $this->asientos->count()
            ),
            'precio' => $this->when(
                isset($this->pivot),
                fn() => [
                    'valor' => number_format($this->pivot->precio, 2, ',', '.') . ' €',
                    'disponible' => $this->pivot->disponible,
                ]
            ),
        ];
    }
}