<?php

namespace Database\Factories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArtistaFactory extends Factory
{
    protected $model = Artista::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->sentence(3),
            // ahora el artista es un catálogo independiente; la asociación con eventos
            // se hace mediante la tabla pivote `artista_evento` o en seeders/tests
            'descripcion' => $this->faker->paragraphs(3, true),
            'imagen_url' => $this->faker->imageUrl(640, 480, 'artists', true),
        ];
    }
}