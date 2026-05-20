<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrecioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sector' => new SectorResource($this->whenLoaded('sector')),
            'precio' => number_format($this->precio, 2, ',', '.') . ' €',
            'precio_raw' => $this->precio,
            'disponible' => $this->disponible,
        ];
    }
}