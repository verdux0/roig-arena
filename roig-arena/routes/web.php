<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\PaginaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\EntradaController;
use App\Http\Controllers\ArtistaController;
use App\Http\Controllers\SectorController;

Route::get('/', [PaginaController::class, 'home'])->name('home');
Route::get('/eventos', [PaginaController::class, 'eventosIndex'])->name('eventos.index');
Route::get('/eventos/{evento}', [PaginaController::class, 'eventosShow'])->name('eventos.show');
Route::get('/compra/{evento}', [CompraController::class, 'show'])->name('compra.buy');
// Route::get('/compra/{evento}/setmap', [CompraController::class,'setmap'])->name('compra.setmap');

// Rutas de login
Route::view('/login', 'auth.login')->name('login');
Route::post('/login', [AuthController::class, 'loginWeb'])->name('login.post');

// Rutas de registro
Route::view('/register', 'auth.register')->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Ruta de logout
Route::post('/logout', [AuthController::class, 'logoutWeb'])->middleware('auth')->name('logout.post');

// Rutas informacion usuario
Route::view('/dashboard', 'auth.dashboard')->middleware('auth')->name('dashboard');
Route::view('/profile', 'auth.profile')->middleware('auth')->name('profile');
Route::get('/mis-eventos', [EventoController::class, 'misEventos'])->middleware('auth')->name('mis-eventos');
Route::get('/mis-eventos-info', [EventoController::class, 'misEventosInfo'])->middleware('auth')->name('mis-eventos.info');
Route::get('/mi-evento-info/{id}', [EventoController::class, 'miEventoInfo'])->middleware('auth')->name('mi-evento.info');
Route::delete('/entradas/{id}', [EntradaController::class, 'destroy'])->middleware('auth')->name('entradas.destroy');

// Rutas de pagos pendientes
Route::get('/mis-pagos-pendientes', [CompraController::class, 'misPagosPendientes'])->middleware('auth')->name('mis-pagos-pendientes');
Route::post('/mis-pagos-pendientes/pagar', [CompraController::class, 'procesarPagoPendiente'])->middleware('auth')->name('mis-pagos-pendientes.pagar');

// Rutas admin (web)
Route::middleware(['auth', 'admin'])
	->prefix('admin')
	->name('admin.')
	->group(function () {
		Route::get('/eventos/create', [EventoController::class, 'create'])->name('eventos.create');
		Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
		Route::patch('/eventos/{id}', [EventoController::class, 'update'])->name('eventos.update');
		Route::delete('/eventos/{id}', [EventoController::class, 'destroy'])->name('eventos.destroy');
		Route::delete('/eventos/{eventoId}/artistas/{artistaId}', [EventoController::class, 'detachArtista'])->name('eventos.artistas.destroy');
		Route::post('/eventos/{eventoId}/sectores', [EventoController::class, 'attachSector'])->name('eventos.sectores.store');
		Route::delete('/eventos/{eventoId}/sectores/{sectorId}', [EventoController::class, 'detachSector'])->name('eventos.sectores.destroy');

		Route::patch('/precios/{id}', [EventoController::class, 'updateSectorPrice'])->name('precios.update');
		Route::post('/precios/bulk-delete', [EventoController::class, 'bulkDeletePrecios'])->name('precios.bulkDelete');
        Route::delete('/precios/{id}', [EventoController::class, 'disableSector'])->name('sectores.disable');

		// Ruta de administración para abrir el editor visual de sectores. Recibe evento_id en el controller
		Route::get('/eventos/{eventoId}/sectores/editor', [EventoController::class, 'sectorEditor'])->name('eventos.sectores.editor');

        Route::delete('/eventos/{id}', [EventoController::class, 'destroy'])->name('eventos.destroy');
        Route::post('/eventos/{eventoId}/artistas', [EventoController::class, 'attachArtista'])->name('eventos.artistas.store');

		Route::get('/artistas/create', [ArtistaController::class, 'create'])->name('artistas.create');
		Route::post('/artistas', [ArtistaController::class, 'store'])->name('artistas.store');
		Route::delete('/artistas/{id}', [ArtistaController::class, 'destroy'])->name('artistas.destroy');

        // Ruta POST para guardar un sector nuevo. Esta ruta recibe los datos del rectángulo y el metadato del sector.
        Route::post('/sectores', [SectorController::class, 'store'])->name('sectores.store');
        // Ruta PATCH para editar un sector. Usada para cambios de nombre, color, descripción y, si lo permite, límites.
        Route::patch('/sectores/{id}', [SectorController::class, 'update'])->name('sectores.update');
        // Ruta DELETE para borrar un sector. Devolver error claro si el sector no puede eliminarse.
        Route::delete('/sectores/{id}', [SectorController::class, 'destroy'])->name('sectores.destroy');

	});

// Ruta opcional para conservar la vista inicial de Laravel como referencia.
Route::view('/welcome', 'welcome')->name('welcome');
