<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Sector;
use App\Models\Asiento;
use App\Models\Evento;
use App\Models\User;
use App\Models\EstadoAsiento;
use App\Models\Entrada;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ModeloTest extends TestCase
{
    use RefreshDatabase;

    public function test_sector_tiene_relacion_con_asientos()
    {
        $sector = Sector::factory()->create();
        $asiento = Asiento::factory()->create(['sector_id' => $sector->id]);

        $this->assertTrue($sector->asientos->contains($asiento));
    }

    public function test_asiento_pertenece_a_sector()
    {
        $sector = Sector::factory()->create();
        $asiento = Asiento::factory()->create(['sector_id' => $sector->id]);

        $this->assertEquals($sector->id, $asiento->sector->id);
    }

    public function test_evento_tiene_soft_deletes()
    {
        $evento = Evento::factory()->create();
        $evento->delete();

        $this->assertSoftDeleted('eventos', ['id' => $evento->id]);
    }

    public function test_user_tiene_soft_deletes()
    {
        $user = User::factory()->create();
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_estado_asiento_verifica_expiracion()
    {
        $reserva = EstadoAsiento::factory()->create([
            'reservado_hasta' => now()->subMinutes(20),
        ]);

        $this->assertTrue($reserva->haExpirado());
    }

    public function test_estado_asiento_calcula_tiempo_restante()
    {
        $reserva = EstadoAsiento::factory()->create([
            'reservado_hasta' => now()->addMinutes(10),
        ]);

        $this->assertGreaterThanOrEqual(9, $reserva->tiempoRestante());
    }

    public function test_asiento_verifica_disponibilidad_para_evento()
    {
        $evento = Evento::factory()->create();
        $asiento = Asiento::factory()->create();


        EstadoAsiento::factory()->create([
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
            'estado' => 'DISPONIBLE',
        ]);

        $this->assertTrue($asiento->estaDisponible($evento->id));
        // $this->assertFalse($asiento->fresh()->estaDisponible($evento->id));
    }

    public function test_user_puede_tener_multiples_reservas()
    {
        $user = User::factory()->create();
        EstadoAsiento::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->reservas);
    }

    public function test_entrada_genera_codigo_qr_unico()
    {
        $entrada1 = Entrada::factory()->create();
        $entrada2 = Entrada::factory()->create();

        $this->assertNotEquals($entrada1->codigo_qr, $entrada2->codigo_qr);
    }
}