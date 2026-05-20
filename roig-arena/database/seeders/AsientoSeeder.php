<?php

namespace Database\Seeders;

use App\Models\Sector;
use App\Models\Asiento;
use Illuminate\Database\Seeder;

class AsientoSeeder extends Seeder
{

    private array $asientos = [];

    public function run(): void
    {
        $sectores = Sector::activos()->get();
        $totalAsientos = 0;
        $this->asientos = [];

        /** @var Sector $sector */
        foreach ($sectores as $sector) {
            $asientosSector = $this->generarAsientosPorSector($sector);
            $this->asientos = array_merge($this->asientos, $asientosSector);
            $totalAsientos += count($asientosSector);
        }

        // Llamamos a la función para generar estado_asientos para el primer evento
        $this->generarEstadoAsientosParaEvento($this->asientos, 1); // Asumiendo que el primer evento tiene ID 1

        $this->command->info("✅ Asientos creados: {$totalAsientos}");
    }

    private function generarAsientosPorSector(Sector $sector): array
    {
        $asientos = [];

        // Oeste 401, 402, 403: 3 filas x 5 asientos = 15 asientos por sector
        if (preg_match('/^Oeste (401|402|403)$/i', $sector->nombre)) {
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = $this->crearAsientoSector($sector, $fila, $numero);
                }
            }
        }

        // Sur 301, 302, 303: 3 filas x 5 asientos = 15 asientos por sector
        elseif (preg_match('/^Sur (301|302|303)$/i', $sector->nombre)) {
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = $this->crearAsientoSector($sector, $fila, $numero);
                }
            }
        }

        // Este 201, 202, 203: 3 filas x 5 asientos = 15 asientos por sector
        elseif (preg_match('/^Este (201|202|203)$/i', $sector->nombre)) {
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = $this->crearAsientoSector($sector, $fila, $numero);
                }
            }
        }

        // Pista interior A/B/C: 4 filas x 5 asientos por sector
        elseif (str_starts_with($sector->nombre, 'PISTA')) {
            for ($fila = 1; $fila <= 4; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = $this->crearAsientoSector($sector, $fila, $numero);
                }
            }
        }
        // CLUB: 10 filas x 20 asientos = 200 asientos
        elseif ($sector->nombre === 'CLUB') {
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }
        // JOHNNIE WALKER: 8 filas x 15 asientos = 120 asientos
        elseif ($sector->nombre === 'JOHNNIE WALKER') {
            for ($fila = 1; $fila <= 8; $fila++) {
                for ($numero = 1; $numero <= 15; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }
        // PISTA: 30 filas x 25 asientos = 750 asientos
        elseif ($sector->nombre === 'PISTA') {
            for ($fila = 1; $fila <= 3; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }
        // FRONT STAGE: 5 filas x 30 asientos = 150 asientos
        elseif ($sector->nombre === 'FRONT STAGE') {
            for ($fila = 1; $fila <= 5; $fila++) {
                for ($numero = 1; $numero <= 5; $numero++) {
                    $asientos[] = Asiento::create([
                        'sector_id' => $sector->id,
                        'fila' => (string) $fila,
                        'numero' => $numero,
                    ]);
                }
            }
        }

        return $asientos;
    }

    private function crearAsientoSector(Sector $sector, int $filaRel, int $numeroRel)
    {
        $fila = $sector->fila_inicio + $filaRel - 1;
        $numero = $sector->columna_inicio + $numeroRel - 1;

        return Asiento::create([
            'sector_id' => $sector->id,
            'fila' => (string) $fila,
            'numero' => $numero,
        ]);
    }

    private function generarEstadoAsientosParaEvento($asientos, $eventoId): void
    {
        foreach ($asientos as $asiento) {
            $asiento->estadoAsientos()->create([
                'evento_id' => $eventoId,
                'asiento_id' => $asiento->id,
                'estado' => 1, // DISPONIBLE
                'reservado_hasta' => null,
            ]);
        }
    }
}
