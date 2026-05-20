<?php

namespace Database\Factories;

use App\Models\Precio;
use App\Models\Evento;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrecioFactory extends Factory
{
    protected $model = Precio::class;

    public function definition(): array
    {
        return [
            'evento_id' => Evento::factory(),
            'sector_id' => Sector::factory(),
            'precio' => $this->faker->randomFloat(2, 20, 150),
            'disponible' => true,
        ];
    }
}