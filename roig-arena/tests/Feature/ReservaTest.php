<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Evento;
use App\Models\Sector;
use App\Models\Asiento;
use App\Models\Precio;
use App\Models\EstadoAsiento;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReservaTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_puede_reservar_asiento_disponible()
    {
        $user = User::factory()->create();
        $evento = Evento::factory()->create();
        $sector = Sector::factory()->create();
        $asiento = Asiento::factory()->create(['sector_id' => $sector->id]);
        
        Precio::factory()->create([
            'evento_id' => $evento->id,
            'sector_id' => $sector->id,
            'precio' => 50.00,
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservas', [
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('estado_asientos', [
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
            'user_id' => $user->id,
            'estado' => 'RESERVADO',
        ]);
    }

    public function test_no_puede_reservar_asiento_ya_reservado()
    {
        $user = User::factory()->create();
        $evento = Evento::factory()->create();
        $asiento = Asiento::factory()->create();

        EstadoAsiento::factory()->create([
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
            'estado' => 'RESERVADO',
        ]);

        $response = $this->actingAs($user)->postJson('/api/reservas', [
            'evento_id' => $evento->id,
            'asiento_id' => $asiento->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_usuario_puede_ver_sus_reservas()
    {
        $user = User::factory()->create();
        
        EstadoAsiento::factory()->count(3)->create([
            'user_id' => $user->id,
            'estado' => 'RESERVADO',
        ]);

        $response = $this->actingAs($user)->getJson('/api/reservas');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_usuario_puede_cancelar_su_reserva()
    {
        $user = User::factory()->create();
        $reserva = EstadoAsiento::factory()->create([
            'user_id' => $user->id,
            'estado' => 'RESERVADO',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/reservas/{$reserva->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('estado_asientos', [
            'id' => $reserva->id,
        ]);
    }

    public function test_usuario_no_puede_cancelar_reserva_de_otro()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        $reserva = EstadoAsiento::factory()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1)->deleteJson("/api/reservas/{$reserva->id}");

        $response->assertStatus(400);
    }
}