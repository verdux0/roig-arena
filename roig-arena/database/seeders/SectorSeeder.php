<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectores = [
            // Lateral derecho.
            ['nombre' => 'ESTE 201', 'descripcion' => 'Grada lateral este', 'cantidad_filas' => 3, 'cantidad_columnas' => 6, 'fila_inicio' => 1, 'fila_fin' => 3, 'columna_inicio' => 16, 'columna_fin' => 21, 'color_hex' => '#5AA3E6', 'activo' => true],
            ['nombre' => 'ESTE 202', 'descripcion' => 'Grada lateral este', 'cantidad_filas' => 3, 'cantidad_columnas' => 6, 'fila_inicio' => 4, 'fila_fin' => 6, 'columna_inicio' => 16, 'columna_fin' => 21, 'color_hex' => '#4B93D4', 'activo' => true],
            ['nombre' => 'ESTE 203', 'descripcion' => 'Grada lateral este', 'cantidad_filas' => 3, 'cantidad_columnas' => 6, 'fila_inicio' => 7, 'fila_fin' => 9, 'columna_inicio' => 16, 'columna_fin' => 21, 'color_hex' => '#3D82C2', 'activo' => true],

            // Banda inferior.
            ['nombre' => 'SUR 301', 'descripcion' => 'Anillo sur opuesto al escenario', 'cantidad_filas' => 3, 'cantidad_columnas' => 5, 'fila_inicio' => 4, 'fila_fin' => 6, 'columna_inicio' => 11, 'columna_fin' => 15, 'color_hex' => '#57BB8A', 'activo' => true],
            ['nombre' => 'SUR 302', 'descripcion' => 'Anillo sur opuesto al escenario', 'cantidad_filas' => 3, 'cantidad_columnas' => 5, 'fila_inicio' => 7, 'fila_fin' => 9, 'columna_inicio' => 6, 'columna_fin' => 10, 'color_hex' => '#4EB07C', 'activo' => true],
            ['nombre' => 'SUR 303', 'descripcion' => 'Anillo sur opuesto al escenario', 'cantidad_filas' => 3, 'cantidad_columnas' => 5, 'fila_inicio' => 7, 'fila_fin' => 9, 'columna_inicio' => 11, 'columna_fin' => 15, 'color_hex' => '#47A86F', 'activo' => true],

            // Lateral izquierdo.
            ['nombre' => 'OESTE 401', 'descripcion' => 'Grada lateral oeste', 'cantidad_filas' => 3, 'cantidad_columnas' => 6, 'fila_inicio' => 1, 'fila_fin' => 3, 'columna_inicio' => 1, 'columna_fin' => 6, 'color_hex' => '#9A7FD8', 'activo' => true],
            ['nombre' => 'OESTE 402', 'descripcion' => 'Grada lateral oeste', 'cantidad_filas' => 3, 'cantidad_columnas' => 6, 'fila_inicio' => 4, 'fila_fin' => 6, 'columna_inicio' => 1, 'columna_fin' => 6, 'color_hex' => '#8B6EC6', 'activo' => true],
            ['nombre' => 'OESTE 403', 'descripcion' => 'Grada lateral oeste', 'cantidad_filas' => 3, 'cantidad_columnas' => 6, 'fila_inicio' => 7, 'fila_fin' => 9, 'columna_inicio' => 1, 'columna_fin' => 6, 'color_hex' => '#7C5DB4', 'activo' => true],

            // Zona interior (pista) como sectores de asientos.
            ['nombre' => 'PISTA A', 'descripcion' => 'Sector interior de pista', 'cantidad_filas' => 4, 'cantidad_columnas' => 6, 'fila_inicio' => 1, 'fila_fin' => 4, 'columna_inicio' => 6, 'columna_fin' => 11, 'color_hex' => '#EA8A4B', 'activo' => true],
            ['nombre' => 'PISTA B', 'descripcion' => 'Sector interior de pista', 'cantidad_filas' => 4, 'cantidad_columnas' => 6, 'fila_inicio' => 1, 'fila_fin' => 4, 'columna_inicio' => 12, 'columna_fin' => 17, 'color_hex' => '#DE7A3F', 'activo' => true],
            ['nombre' => 'PISTA C', 'descripcion' => 'Sector interior de pista', 'cantidad_filas' => 4, 'cantidad_columnas' => 6, 'fila_inicio' => 5, 'fila_fin' => 8, 'columna_inicio' => 6, 'columna_fin' => 11, 'color_hex' => '#D06B33', 'activo' => true],

            // Sectores especiales requeridos por la lógica de asientos
            ['nombre' => 'PISTA', 'descripcion' => 'Pista general', 'cantidad_filas' => 3, 'cantidad_columnas' => 5, 'fila_inicio' => 1, 'fila_fin' => 3, 'columna_inicio' => 6, 'columna_fin' => 10, 'color_hex' => '#F2B66E', 'activo' => true],
            ['nombre' => 'CLUB', 'descripcion' => 'Zona club VIP', 'cantidad_filas' => 3, 'cantidad_columnas' => 5, 'fila_inicio' => 1, 'fila_fin' => 3, 'columna_inicio' => 18, 'columna_fin' => 22, 'color_hex' => '#CFA3F5', 'activo' => true],
            ['nombre' => 'JOHNNIE WALKER', 'descripcion' => 'Zona patrocinada', 'cantidad_filas' => 3, 'cantidad_columnas' => 5, 'fila_inicio' => 1, 'fila_fin' => 3, 'columna_inicio' => 23, 'columna_fin' => 27, 'color_hex' => '#B89EE1', 'activo' => true],
            ['nombre' => 'FRONT STAGE', 'descripcion' => 'Zona frente al escenario', 'cantidad_filas' => 3, 'cantidad_columnas' => 10, 'fila_inicio' => 1, 'fila_fin' => 3, 'columna_inicio' => 1, 'columna_fin' => 10, 'color_hex' => '#FF7F50', 'activo' => true],
        ];

        foreach ($sectores as $sector) {
            Sector::create($sector);
        }

        $this->command->info('✅ Sectores creados: ' . count($sectores));
    }
}
