<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\IsAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IsAdminMiddlewareTest extends TestCase
{

    use RefreshDatabase;
    public function test_permite_acceso_a_admin()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $request = Request::create('/admin/eventos', 'POST');
        $request->setUserResolver(fn() => $user);
        
        $middleware = new IsAdmin();
        $response = $middleware->handle($request, fn() => response('OK'));
        
        $this->assertEquals('OK', $response->getContent());
    }
    
    public function test_bloquea_acceso_a_no_admin()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $request = Request::create('/admin/eventos', 'POST');
        $request->setUserResolver(fn() => $user);
        
        $middleware = new IsAdmin();
        $response = $middleware->handle($request, fn() => response('OK'));
        
        $this->assertEquals(403, $response->getStatusCode());
    }
}