<?php

namespace Database\Factories;

use App\Models\EstadoAsiento;
use App\Models\Evento;
use App\Models\Asiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EstadoAsientoFactory extends Factory
{
    protected $model = EstadoAsiento::class;

    public function definition(): array
    {
        return [
            'evento_id' => Evento::factory(),
            'asiento_id' => Asiento::factory(),
            'user_id' => User::factory(),
            'estado' => 'RESERVADO',
            'reservado_hasta' => now()->addMinutes(15),
        ];
    }

    public function vendido(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'OCUPADO',
            'reservado_hasta' => null,
        ]);
    }

    public function expirado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'RESERVADO',
            'reservado_hasta' => now()->subMinutes(20),
        ]);
    }
}