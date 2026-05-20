<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Evento;
use App\Models\Asiento;
use App\Models\EstadoAsiento;
use App\Models\Precio;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompraTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_confirmar_compra()
    {
        $user = User::factory()->create();
        $evento = Evento::factory()->create();
        $asiento = Asiento::factory()->create();
        
        // Crear el precio para este evento y sector
        Precio::factory()->create([
            'evento_id' => $evento->id,
            'sector_id' => $asiento->sector_id,
        ]);
        
        $reserva = EstadoAsiento::factory()->create([
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
            'user_id' => $user->id,
            'estado' => 'RESERVADO',
            'reservado_hasta' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($user)->postJson('/api/compras', [
            'reservas' => [$reserva->id],
            'metodo_pago' => 'tarjeta',
            'evento_id' => $evento->id,
            'user_id' => $user->id,
            'asientos' => [$asiento->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('entradas', [
            'user_id' => $user->id,
            'evento_id' => $evento->id,
        ]);
        $this->assertDatabaseHas('estado_asientos', [
            'id' => $reserva->id,
            'estado' => 'OCUPADO',
        ]);
    }

    public function test_no_puede_comprar_reserva_expirada()
    {
        $user = User::factory()->create();
        $reserva = EstadoAsiento::factory()->expirado()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->postJson('/api/compras', [
            'reservas' => [$reserva->id],
        ]);

        $response->assertStatus(400);
    }

    public function test_no_puede_comprar_reserva_de_otro_usuario()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $reserva = EstadoAsiento::factory()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->postJson('/api/compras', [
            'reservas' => [$reserva->id],
        ]);

        $response->assertStatus(400);
    }

    public function test_entrada_genera_codigo_qr_automaticamente()
    {
        $user = User::factory()->create();
        $asiento = Asiento::factory()->create();
        $evento = Evento::factory()->create();
        
        // Crear el precio
        Precio::factory()->create([
            'evento_id' => $evento->id,
            'sector_id' => $asiento->sector_id,
        ]);
        
        $reserva = EstadoAsiento::factory()->create([
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
            'user_id' => $user->id,
            'estado' => 'RESERVADO',
            'reservado_hasta' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)->postJson('/api/compras', [
            'reservas' => [$reserva->id],
        ]);

        $entrada = $user->entradas()->first();
        $this->assertNotNull($entrada->codigo_qr);
        $this->assertEquals(32, strlen($entrada->codigo_qr));
    }
}