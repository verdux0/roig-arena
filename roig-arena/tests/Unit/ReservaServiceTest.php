<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\ReservaService;
use App\Models\User;
use App\Models\Evento;
use App\Models\Sector;
use App\Models\Asiento;
use App\Models\Precio;
use App\Models\EstadoAsiento;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservaServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReservaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReservaService();
    }

    public function test_puede_reservar_asiento_disponible()
    {
        $user = User::factory()->create();
        $evento = Evento::factory()->create();
        $sector = Sector::factory()->create();
        $asiento = Asiento::factory()->create(['sector_id' => $sector->id]);
        
        Precio::factory()->create([
            'evento_id' => $evento->id,
            'sector_id' => $sector->id,
        ]);

        $reserva = $this->service->reservarAsiento($evento->id, $asiento->id, $user->id);

        $this->assertNotNull($reserva);
        $this->assertEquals('RESERVADO', $reserva->estado);
    }

    public function test_no_puede_reservar_asiento_ocupado()
    {
        $user = User::factory()->create();
        $evento = Evento::factory()->create();
        $asiento = Asiento::factory()->create();

        EstadoAsiento::factory()->create([
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
            'estado' => 'RESERVADO',
        ]);

        $this->expectException(\Exception::class);
        $this->service->reservarAsiento($evento->id, $asiento->id, $user->id);
    }

    public function test_puede_cancelar_reserva()
    {
        $user = User::factory()->create();
        $reserva = EstadoAsiento::factory()->create([
            'user_id' => $user->id,
            'estado' => 'RESERVADO',
        ]);

        $resultado = $this->service->cancelarReserva($reserva->id, $user->id);

        $this->assertTrue($resultado);
        $this->assertDatabaseMissing('estado_asientos', ['id' => $reserva->id]);
    }

    public function test_obtiene_reservas_activas()
    {
        $user = User::factory()->create();
        
        EstadoAsiento::factory()->count(2)->create([
            'user_id' => $user->id,
            'estado' => 'RESERVADO',
            'reservado_hasta' => now()->addMinutes(10),
        ]);

        $reservas = $this->service->obtenerReservasActivas($user->id);

        $this->assertCount(2, $reservas);
    }

    public function test_no_obtiene_reservas_expiradas()
    {
        $user = User::factory()->create();
        
        EstadoAsiento::factory()->create([
            'user_id' => $user->id,
            'reservado_hasta' => now()->subMinutes(20),
        ]);

        $reservas = $this->service->obtenerReservasActivas($user->id);

        $this->assertCount(0, $reservas);
    }
}