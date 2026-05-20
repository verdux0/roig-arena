<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Sector;
use App\Services\SectorGeometryService;

class SectorDimensionsTest extends TestCase
{
    public function test_compute_dimensions_basic()
    {
        $service = new SectorGeometryService();

        $s = new Sector([
            'fila_inicio' => 1,
            'fila_fin' => 3,
            'columna_inicio' => 3,
            'columna_fin' => 6,
        ]);

        $dims = $service->calcularDimensiones($s);

        $this->assertEquals(3, $dims['cantidad_filas']);
        $this->assertEquals(4, $dims['cantidad_columnas']);
        $this->assertEquals(12, $dims['total_asientos']);
    }
}
