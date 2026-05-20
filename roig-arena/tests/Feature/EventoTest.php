<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Evento;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EventoTest extends TestCase
{
    use RefreshDatabase;

    public function test_puede_listar_eventos_publicos()
    {
        Evento::factory()->count(3)->create();

        $response = $this->getJson('/api/eventos');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_puede_ver_detalle_evento()
    {
        $evento = Evento::factory()->create();

        $response = $this->getJson("/api/eventos/{$evento->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'evento' => [
                        'id' => $evento->id,
                        'nombre' => $evento->nombre,
                    ],
                ],
            ]);
    }

    public function test_admin_puede_crear_evento()
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->postJson('/api/admin/eventos', [
            'nombre' => 'Concierto Rock',
            'descripcion_corta' => 'Gran concierto',
            'descripcion_larga' => 'Descripción detallada del concierto',
            'fecha' => '2026-12-31',
            'hora' => '20:00',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('eventos', [
            'nombre' => 'Concierto Rock',
        ]);
    }

    public function test_usuario_normal_no_puede_crear_evento()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->postJson('/api/admin/eventos', [
            'nombre' => 'Concierto Rock',
            'descripcion_corta' => 'Gran concierto',
            'descripcion_larga' => 'Descripción detallada del concierto',
            'fecha' => '2026-12-31',
            'hora' => '20:00',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_puede_actualizar_evento()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $evento = Evento::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/admin/eventos/{$evento->id}", [
            'nombre' => 'Nuevo Nombre',
            'descripcion_corta' => $evento->descripcion_corta,
            'descripcion_larga' => $evento->descripcion_larga,
            'fecha' => $evento->fecha->format('Y-m-d'),
            'hora' => $evento->hora->format('H:i'),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('eventos', [
            'id' => $evento->id,
            'nombre' => 'Nuevo Nombre',
        ]);
    }

    public function test_admin_puede_eliminar_evento_sin_entradas()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $evento = Evento::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/admin/eventos/{$evento->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('eventos', ['id' => $evento->id]);
    }
}