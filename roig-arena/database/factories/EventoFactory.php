<?php

namespace Database\Factories;

use App\Models\Evento;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventoFactory extends Factory
{
    protected $model = Evento::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->sentence(3),
            'descripcion_corta' => $this->faker->sentence(10),
            'descripcion_larga' => $this->faker->paragraphs(3, true),
            'fecha' => $this->faker->dateTimeBetween('now', '+1 year'),
            'hora' => $this->faker->time('H:i'),
            'poster_url' => $this->faker->imageUrl(640, 480, 'events', true),
            'poster_ancho_url' => $this->faker->imageUrl(1280, 720, 'events', true),
        ];
    }
}