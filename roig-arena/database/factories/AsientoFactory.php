<?php

namespace Database\Factories;

use App\Models\Asiento;
use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsientoFactory extends Factory
{
    protected $model = Asiento::class;

    public function definition(): array
    {
        return [
            'sector_id' => Sector::factory(),
            'fila' => $this->faker->numberBetween(1, 20),
            'numero' => $this->faker->numberBetween(1, 30),
        ];
    }
}