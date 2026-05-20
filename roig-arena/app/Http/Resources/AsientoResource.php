<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AsientoResource extends JsonResource
{
    protected $eventoId;

    public function __construct($resource, $eventoId = null)
    {
        parent::__construct($resource);
        $this->eventoId = $eventoId;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sector' => $this->sector->nombre,
            'sector_id' => $this->sector_id,
            'fila' => $this->fila,
            'numero' => $this->numero,
            'nombre_completo' => $this->nombreCompleto(),
            'disponible' => $this->when(
                $this->eventoId,
                fn() => $this->estaDisponible($this->eventoId)
            ),
        ];
    }

    public static function collectionWithEvento($resource, $eventoId)
    {
        return $resource->map(function ($item) use ($eventoId) {
            return new static($item, $eventoId);
        });
    }
}