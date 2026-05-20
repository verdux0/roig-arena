<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CompraService;
use App\Models\User;
use App\Models\Evento;
use App\Models\Asiento;
use App\Models\EstadoAsiento;
use App\Models\Precio;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompraServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CompraService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CompraService();
    }

    public function test_puede_procesar_compra()
    {
        $user = User::factory()->create();
        $evento = Evento::factory()->create();
        $asiento = Asiento::factory()->create();
        
        // Crear precio
        Precio::factory()->create([
            'evento_id' => $evento->id,
            'sector_id' => $asiento->sector_id,
        ]);
        
        $reserva = EstadoAsiento::factory()->create([
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
            'estado' => 'RESERVADO',
            'user_id' => $user->id,
            'reservado_hasta' => now()->addMinutes(10),
        ]);

        $entradas = $this->service->procesarCompra([$reserva->id], $user->id);

        $this->assertCount(1, $entradas);
        $this->assertEquals('OCUPADO', $reserva->fresh()->estado);
    }

    public function test_no_puede_procesar_compra_expirada()
    {
        $user = User::factory()->create();
        $reserva = EstadoAsiento::factory()->expirado()->create([
            'user_id' => $user->id,
        ]);

        $this->expectException(\Exception::class);
        $this->service->procesarCompra([$reserva->id], $user->id);
    }

    public function test_puede_procesar_compra_multiple()
    {
        $user = User::factory()->create();
        $evento = Evento::factory()->create();
        
        // Crear múltiples asientos y precios
        $asientos = Asiento::factory()->count(3)->create();
        foreach ($asientos as $asiento) {
            Precio::factory()->create([
                'evento_id' => $evento->id,
                'sector_id' => $asiento->sector_id,
            ]);
        }
        
        $reservas = EstadoAsiento::factory()->count(3)->create([
            'evento_id' => $evento->id,
            'asiento_id' => function() use ($asientos) {
                static $index = 0;
                return $asientos[$index++ % 3]->id;
            },
            'user_id' => $user->id,
            'reservado_hasta' => now()->addMinutes(10),
        ]);

        $entradas = $this->service->procesarCompra(
            $reservas->pluck('id')->toArray(),
            $user->id
        );

        $this->assertCount(3, $entradas);
    }

    public function test_rollback_si_falla_una_compra()
    {
        $user = User::factory()->create();
        $reserva1 = EstadoAsiento::factory()->create([
            'user_id' => $user->id,
            'reservado_hasta' => now()->addMinutes(10),
        ]);
        $reserva2 = EstadoAsiento::factory()->expirado()->create([
            'user_id' => $user->id,
        ]);

        try {
            $this->service->procesarCompra([$reserva1->id, $reserva2->id], $user->id);
        } catch (\Exception $e) {
            // Esperado
        }

        $this->assertEquals('RESERVADO', $reserva1->fresh()->estado);
        $this->assertDatabaseMissing('entradas', ['user_id' => $user->id]);
    }
}