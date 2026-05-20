<?php

namespace Database\Seeders;

use App\Models\Evento;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class EventoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');

        // Mantener algunos nombres conocidos para compatibilidad con reglas de precio
        $presets = [
            [
                'nombre' => 'Concierto Rock 2026',
                'tipo' => 'concierto',
            ],
            [
                'nombre' => 'Final Copa del Rey',
                'tipo' => 'deporte',
            ],
            [
                'nombre' => 'Festival Electrónica',
                'tipo' => 'festival',
            ],
        ];

        $total = 0;

        // Crear eventos preset
        foreach ($presets as $p) {
            Evento::create([
                'nombre' => $p['nombre'],
                'descripcion_corta' => $faker->sentence(6),
                'descripcion_larga' => $faker->paragraph(3),
                'poster_url' => 'https://picsum.photos/seed/event_' . str_replace(' ', '_', $p['nombre']) . '/800/600',
                'poster_ancho_url' => 'https://picsum.photos/seed/event_wide_' . str_replace(' ', '_', $p['nombre']) . '/1280/720',
                'fecha' => $faker->dateTimeBetween('2026-05-01', '2026-12-31')->format('Y-m-d'),
                'hora' => $faker->time('H:i'),
            ]);

            $total++;
        }

        // Crear eventos adicionales aleatorios
        for ($i = 0; $i < 4; $i++) {
            Evento::create([
                'nombre' => $faker->company . ' Live',
                'descripcion_corta' => $faker->sentence(5),
                'descripcion_larga' => $faker->paragraph(2),
                'poster_url' => 'https://picsum.photos/seed/event_rand_' . $i . '/800/600',
                'poster_ancho_url' => 'https://picsum.photos/seed/event_rand_wide_' . $i . '/1280/720',
                'fecha' => $faker->dateTimeBetween('2026-01-01', '2026-12-31')->format('Y-m-d'),
                'hora' => $faker->time('H:i'),
            ]);

            $total++;
        }

        $this->command->info('✅ Eventos creados: ' . $total);
    }
}
