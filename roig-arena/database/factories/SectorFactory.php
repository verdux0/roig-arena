<?php

namespace Database\Factories;

use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectorFactory extends Factory
{
    protected $model = Sector::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Sector ' . $this->faker->numberBetween(101, 323),
            'descripcion' => $this->faker->optional()->sentence(),
            'cantidad_filas' => $this->faker->numberBetween(10, 50),
            'cantidad_columnas' => $this->faker->numberBetween(10, 50),
            'color_hex' => $this->faker->hexColor(),
            'activo' => true,
        ];
    }
}