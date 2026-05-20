<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\LiberarReservasService;
use App\Models\EstadoAsiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LiberarReservasServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LiberarReservasService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LiberarReservasService();
    }

    public function test_libera_reservas_expiradas()
    {
        EstadoAsiento::factory()->count(5)->create([
            'reservado_hasta' => now()->subMinutes(20),
        ]);

        $liberadas = $this->service->liberarExpiradas();

        $this->assertEquals(5, $liberadas);
        $this->assertDatabaseCount('estado_asientos', 0);
    }

    public function test_no_libera_reservas_vendidas()
    {
        EstadoAsiento::factory()->vendido()->create();

        $liberadas = $this->service->liberarExpiradas();

        $this->assertEquals(0, $liberadas);
        $this->assertDatabaseCount('estado_asientos', 1);
    }

    public function test_libera_reservas_de_usuario_especifico()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $reserva1 = EstadoAsiento::factory()->create([
            'user_id' => $user1->id,
            'reservado_hasta' => now()->subMinutes(20),
        ]);
        $reserva2 = EstadoAsiento::factory()->create([
            'user_id' => $user2->id,
            'reservado_hasta' => now()->subMinutes(20),
        ]);

        $liberadas = $this->service->liberarPorUsuario($user1->id);

        $this->assertEquals(1, $liberadas);
        $this->assertDatabaseMissing('estado_asientos', ['id' => $reserva1->id]);
        $this->assertDatabaseHas('estado_asientos', ['id' => $reserva2->id]);
    }
}