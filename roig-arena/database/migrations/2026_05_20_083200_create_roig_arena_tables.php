<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ============================================
        // 1. INFRAESTRUCTURA FÍSICA DEL ESTADIO
        // ============================================

        // Tabla de sectores (zonas del estadio)
        Schema::create('sectores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: VIP, Grada Norte, Tribuna
            $table->text('descripcion')->nullable();
            $table->integer('cantidad_filas');
            $table->integer('cantidad_columnas');
            $table->string('color_hex', 7); // Ej: #FF0000
            $table->boolean('activo')->default(true); // Control global (true = operativo, false = desactivado para TODOS los eventos)

            $table->integer('fila_inicio')->nullable();
            $table->integer('fila_fin')->nullable();
            $table->integer('columna_inicio')->nullable();
            $table->integer('columna_fin')->nullable();
            $table->decimal('posicion_x', 10, 2)->nullable();
            $table->decimal('posicion_y', 10, 2)->nullable();
            $table->integer('orden_visual')->nullable();

            $table->timestamps();
        });

        // Tabla de asientos (butacas físicas)
        Schema::create('asientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained('sectores')->onDelete('cascade');
            $table->string('fila'); // Ej: A, B, C
            $table->integer('numero'); // Ej: 1, 2, 3
            $table->unique(['sector_id', 'fila', 'numero']); // Un asiento único por sector
            $table->timestamps();
        });

        // ============================================
        // 2. LÓGICA DE EVENTOS (TEMPORAL)
        // ============================================

        // Tabla de eventos
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('descripcion_corta', 255); // Para listados
            $table->text('descripcion_larga'); // Para la página del evento
            $table->string('poster_url')->nullable(); // URL de la imagen
            $table->string('poster_ancho_url')->nullable(); // URL de la imagen ancha
            $table->date('fecha'); // Puede ir a más de un evento por día, pero no a la vez
            $table->time('hora')->nullable(); // Hora del evento
            $table->timestamps();
            $table->softDeletes(); // Borrado lógico
        });

        // Tabla de artistas
        Schema::create('artistas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            // ahora los artistas son un catálogo independiente; las relaciones con eventos
            // se gestionan en la tabla pivote `artista_evento` creada más abajo
            $table->text('descripcion')->nullable();
            $table->string('imagen_url')->nullable();
            $table->timestamps();
        });

        // Tabla pivote artista_evento (artistas reutilizables en varios eventos)
        Schema::create('artista_evento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artista_id')->constrained('artistas')->onDelete('cascade');
            $table->foreignId('evento_id')->constrained('eventos')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['artista_id', 'evento_id']);
        });

        // Tabla de precios (precio por sector en cada evento)
        Schema::create('precios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->onDelete('cascade');
            $table->foreignId('sector_id')->constrained('sectores')->onDelete('cascade');
            $table->decimal('precio', 10, 2); // Precio con 2 decimales
            $table->boolean('disponible')->default(true); // Control por evento (true = disponible, false = cerrado para este evento)
            $table->unique(['evento_id', 'sector_id']); // Un precio por sector/evento
            $table->timestamps();
        });

        // Tabla de estado de asientos (control de reservas)
        Schema::create('estado_asientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->onDelete('cascade');
            $table->foreignId('asiento_id')->constrained('asientos')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('estado', ['DISPONIBLE', 'RESERVADO', 'OCUPADO'])->default('DISPONIBLE');
            $table->timestamp('reservado_hasta')->nullable(); // Temporizador de reserva
            $table->unique(['evento_id', 'asiento_id']); // Un estado por asiento/evento

            $table->timestamps();
        });

        // ============================================
        // 3. VENTAS DEFINITIVAS
        // ============================================

        // Tabla de entradas (ventas confirmadas)
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('evento_id')->constrained('eventos')->onDelete('cascade');
            $table->foreignId('asiento_id')->constrained('asientos')->onDelete('cascade');
            $table->decimal('precio_pagado', 10, 2); // Precio al momento de la compra
            $table->string('codigo_qr')->unique(); // Código QR único para validación
            $table->unique(['evento_id', 'asiento_id']); // Una entrada por asiento/evento
            $table->boolean('descargada')->default(false); // Para control de acceso
            $table->boolean('utilizada')->default(false); // Para control de acceso
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar en orden inverso por las dependencias
        Schema::dropIfExists('entradas');
        Schema::dropIfExists('estado_asientos');
        Schema::dropIfExists('precios');
        Schema::dropIfExists('artista_evento');
        Schema::dropIfExists('eventos');
        Schema::dropIfExists('artistas');
        Schema::dropIfExists('asientos');
        Schema::dropIfExists('sectores');
    }
};
