<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->nombre . ' ' . $this->apellido,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'registrado' => $this->created_at->format('d/m/Y'),
        ];
    }
}