<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginRoutesTest extends TestCase
{
    public function test_la_portada_incluye_acceso_al_login(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Iniciar sesión')
            ->assertSee('/login');
    }

    public function test_la_pagina_de_login_es_accesible(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('Acceso de usuario')
            ->assertSee('Entrar');
    }

    public function test_la_vista_welcome_sigue_renderizando(): void
    {
        $response = $this->get('/welcome');

        $response->assertOk();
    }
}
