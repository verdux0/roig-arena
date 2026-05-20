<?php

namespace Database\Seeders;

use App\Models\Evento;
use App\Models\Sector;
use App\Models\Precio;
use Illuminate\Database\Seeder;

class PrecioSeeder extends Seeder
{
    public function run(): void
    {
        $eventos = Evento::all();
        $sectores = Sector::all();
        $totalPrecios = 0;

        foreach ($eventos as $evento) {
            foreach ($sectores as $sector) {
                $precio = $this->calcularPrecio($evento, $sector);
                
                Precio::create([
                    'evento_id' => $evento->id,
                    'sector_id' => $sector->id,
                    'precio' => $precio,
                    'disponible' => true,
                ]);
                
                $totalPrecios++;
            }
        }

        $this->command->info("✅ Precios creados: {$totalPrecios}");
    }

    private function calcularPrecio(Evento $evento, Sector $sector): float
    {
        // Precios base según tipo de sector
        $precioBase = match(true) {
            str_starts_with($sector->nombre, 'Palco') => 150.00,
            $sector->nombre === 'FRONT STAGE' => 120.00,
            $sector->nombre === 'CLUB' => 100.00,
            $sector->nombre === 'JOHNNIE WALKER' => 90.00,
            $sector->nombre === 'PISTA' => 80.00,
            str_starts_with($sector->nombre, 'Sector 10') => 50.00, // 101-122
            str_starts_with($sector->nombre, 'Sector 30') => 40.00, // 301-323
            default => 50.00,
        };

        // Multiplicador según tipo de evento
        $multiplicador = match($evento->nombre) {
            'Final Copa del Rey' => 1.5,
            'Concierto Rock 2026' => 1.3,
            'Festival Electrónica' => 1.2,
            default => 1.0,
        };

        // Añadir una variación aleatoria pequeña para diversificar precios
        $variacion = mt_rand(-10, 15) / 100; // -10% .. +15%

        return round($precioBase * $multiplicador * (1 + $variacion), 2);
    }
}