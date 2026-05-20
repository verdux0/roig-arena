<?php

namespace Database\Factories;

use App\Models\Entrada;
use App\Models\Evento;
use App\Models\Asiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EntradaFactory extends Factory
{
    protected $model = Entrada::class;

    public function definition(): array
    {
        return [
            'evento_id' => Evento::factory(),
            'asiento_id' => Asiento::factory(),
            'user_id' => User::factory(),
            'precio_pagado' => $this->faker->randomFloat(2, 20, 150),
            'codigo_qr' => Str::random(32),
        ];
    }
}