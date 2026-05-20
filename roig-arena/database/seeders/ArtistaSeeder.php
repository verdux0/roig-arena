<?php

namespace Database\Seeders;

use App\Models\Artista;
use App\Models\Evento;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ArtistaSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');
        $eventos = Evento::all();

        if ($eventos->isEmpty()) {
            $this->command->warn('No hay eventos para asociar artistas. Ejecuta primero EventoSeeder.');
            return;
        }

        $total = 0;

        // Crear artistas variados
        for ($i = 0; $i < 12; $i++) {
            $name = $faker->unique()->name();
            $art = Artista::create([
                'nombre' => $name,
                'descripcion' => $faker->paragraph(2),
                'imagen_url' => 'https://picsum.photos/seed/artist_' . $i . '/640/480',
            ]);

            // Asociar a 1-2 eventos aleatorios
            $attachCount = rand(1, 2);
            $eventosAleatorios = $eventos->random($attachCount);
            foreach ($eventosAleatorios as $ev) {
                $art->eventos()->attach($ev->id);
            }

            $total++;
        }

        $this->command->info('✅ Artistas creados: ' . $total);
    }
}