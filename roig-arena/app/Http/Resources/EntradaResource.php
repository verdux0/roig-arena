<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EntradaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo_qr' => $this->codigo_qr,
            'evento' => [
                'id' => $this->evento->id,
                'nombre' => $this->evento->nombre,
                'fecha' => $this->evento->fecha->format('d/m/Y'),
                'hora' => $this->evento->hora ? $this->evento->hora->format('H:i') : null,
                'poster' => $this->evento->poster_url,
            ],
            'asiento' => [
                'id' => $this->asiento->id,
                'nombre' => $this->asiento->nombreCompleto(),
                'sector' => $this->asiento->sector->nombre,
                'fila' => $this->asiento->fila,
                'numero' => $this->asiento->numero,
            ],
            'precio_pagado' => number_format($this->precio_pagado, 2, ',', '.') . ' €',
            'comprador' => $this->user->nombre . ' ' . $this->user->apellido,
            'fecha_compra' => $this->created_at->format('d/m/Y H:i'),
            'valida' => $this->esValida(),
        ];
    }
}