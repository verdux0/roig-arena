<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\AsientoController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ArtistaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas de la API REST para el sistema de venta de entradas Roig Arena
|
*/

// ============================================
// RUTAS PÚBLICAS (sin autenticación)
// ============================================

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'loginApi']);

// Eventos (consulta pública)
Route::get('/eventos', [EventoController::class, 'index']);
Route::get('/eventos/{id}', [EventoController::class, 'show']);

// Sectores (consulta pública)
Route::get('/sectores', [SectorController::class, 'index']);
Route::get('/sectores/buscar', [SectorController::class, 'buscar']);
// Nuevo endpoint: listar sectores vinculados a un evento.
// Devuelve id, nombre, color_hex y límites; acepta `include_asientos=1`.
Route::get('/eventos/{eventoId}/sectores', [SectorController::class, 'porEvento']);

// Asientos (consulta pública)
Route::get('/eventos/{eventoId}/asientos', [AsientoController::class, 'porEvento']);
Route::get('/eventos/{eventoId}/sectores/{sectorId}/asientos', [AsientoController::class, 'porSector']);

// Artistas (consulta pública)
Route::get('/artistas', [ArtistaController::class, 'index']);
Route::get('/artistas/buscar', [ArtistaController::class, 'buscar']);
Route::get('/artistas/{id}', [ArtistaController::class, 'show']);
Route::get('/eventos/{eventoId}/artistas', [ArtistaController::class, 'porEvento']);

// ============================================
// RUTAS PROTEGIDAS (requieren autenticación)
// ============================================

Route::middleware('auth:sanctum')->group(function () {

    // Usuario autenticado
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Reservas (carrito de compras)
    Route::get('/reservas', [ReservaController::class, 'index']);
    Route::post('/reservas', [ReservaController::class, 'store']);
    Route::delete('/reservas/{id}', [ReservaController::class, 'destroy']);

    // Compras
    Route::post('/compras', [CompraController::class, 'store']);
    Route::post('/compras/confirmar', [CompraController::class, 'confirmarCompra']);
    Route::post('/entradas/pago-pendiente', [CompraController::class, 'procesarPagoPendiente']);

    // Entradas
    Route::get('/entradas', [EntradaController::class, 'index']);
    Route::get('/entradas/{id}', [EntradaController::class, 'show']);
    Route::post('/entradas/{id}/descargar', [EntradaController::class, 'marcarDescargada']);
    Route::delete('/entradas/{id}/cancelar', [EntradaController::class, 'cancelarCompra']);

    // Eventos del usuario
    // Route::get('/mis-eventos', [EventoController::class, 'misEventos']);
    // Route::get('/mis-eventos-info', [EventoController::class, 'misEventosInfo']);
    
    Route::get('/eventos/{evento}/asientos', [EventoController::class, 'mostrarTodosLosAsientos']);
});

// ============================================
// RUTAS DE ADMINISTRADOR
// ============================================

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Eventos (CRUD completo)
    Route::post('/eventos', [EventoController::class, 'store']);
    Route::put('/eventos/{id}', [EventoController::class, 'update']);
    Route::delete('/eventos/{id}', [EventoController::class, 'destroy']); // --- IGNORE --- (ya existe en web.php, pero se mantiene aquí para la API REST)

    // Sectores (CRUD completo)
    Route::post('/sectores', [SectorController::class, 'store']);
    Route::put('/sectores/{id}', [SectorController::class, 'update']);
    Route::delete('/sectores/{id}', [SectorController::class, 'destroy']);
    Route::post('/sectores/validar-rectangulo', [SectorController::class, 'validarRectangulo']);

    // Artistas (CRUD completo)
    Route::post('/artistas', [ArtistaController::class, 'store']);
    Route::put('/artistas/{id}', [ArtistaController::class, 'update']);
    Route::delete('/artistas/{id}', [ArtistaController::class, 'destroy']);
});
