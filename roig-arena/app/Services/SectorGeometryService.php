<?php

namespace App\Services;

use App\Models\Sector;
use InvalidArgumentException;

class SectorGeometryService
{
    /**
     * Convierte dos coordenadas en un rectángulo normalizado.
     * Devuelve siempre inicio/fin ordenados y sus dimensiones.
     */
    public function normalizarRectangulo(array $inicio, array $fin): array
    {
        // Primero comprobamos que ambas coordenadas traen los datos mínimos.
        // Sin fila y columna no podemos construir un rectángulo válido.
        if (!isset($inicio['fila'], $inicio['columna'], $fin['fila'], $fin['columna'])) {
            throw new InvalidArgumentException('Las coordenadas deben incluir fila y columna.');
        }

        // Convertimos todo a enteros para evitar problemas con cadenas o valores mixtos.
        $filaInicio = (int) $inicio['fila'];
        $filaFin = (int) $fin['fila'];
        $columnaInicio = (int) $inicio['columna'];
        $columnaFin = (int) $fin['columna'];

        // Validamos que las coordenadas estén dentro de una rejilla real.
        // En este proyecto no tiene sentido trabajar con filas o columnas menores que 1.
        if ($filaInicio < 1 || $filaFin < 1 || $columnaInicio < 1 || $columnaFin < 1) {
            throw new InvalidArgumentException('Filas y columnas deben ser mayores o iguales a 1.');
        }

        // Ordenamos los extremos aunque el usuario haya hecho clic primero en la esquina opuesta.
        // Así siempre obtenemos un rectángulo consistente y predecible.
        $filaMin = min($filaInicio, $filaFin);
        $filaMax = max($filaInicio, $filaFin);
        $columnaMin = min($columnaInicio, $columnaFin);
        $columnaMax = max($columnaInicio, $columnaFin);

        // Además de los límites, devolvemos las dimensiones calculadas.
        // Esto evita repetir la misma resta en controladores o vistas.
        return [
            'fila_inicio' => $filaMin,
            'fila_fin' => $filaMax,
            'columna_inicio' => $columnaMin,
            'columna_fin' => $columnaMax,
            'cantidad_filas' => ($filaMax - $filaMin) + 1,
            'cantidad_columnas' => ($columnaMax - $columnaMin) + 1,
            'total_asientos' => (($filaMax - $filaMin) + 1) * (($columnaMax - $columnaMin) + 1),
        ];
    }

    /**
     * Comprueba si un rectángulo se cruza con otro sector existente.
     */
    public function existeSolapamiento(array $rectangulo, ?int $exceptSectorId = null): bool
    {
        // Normalizamos otra vez los límites por seguridad.
        // Aunque ya vengan ordenados, así evitamos depender del origen de los datos.
        $filaMin = min($rectangulo['fila_inicio'], $rectangulo['fila_fin']);
        $filaMax = max($rectangulo['fila_inicio'], $rectangulo['fila_fin']);
        $columnaMin = min($rectangulo['columna_inicio'], $rectangulo['columna_fin']);
        $columnaMax = max($rectangulo['columna_inicio'], $rectangulo['columna_fin']);

        // Buscamos cualquier sector que interseque con el nuevo rectángulo tanto en filas como en columnas.
        // La condición solo es verdadera si ambas dimensiones se solapan a la vez.
        return Sector::query()
            ->where(function ($query) use ($filaMin, $filaMax, $columnaMin, $columnaMax) {
                $query->where(function ($subQuery) use ($filaMin, $filaMax) {
                    // Solape en filas: el sector existente debe tocar el rango vertical del nuevo rectángulo.
                    $subQuery->where('fila_inicio', '<=', $filaMax)
                        ->where('fila_fin', '>=', $filaMin);
                })->where(function ($subQuery) use ($columnaMin, $columnaMax) {
                    // Solape en columnas: el sector existente debe tocar el rango horizontal del nuevo rectángulo.
                    $subQuery->where('columna_inicio', '<=', $columnaMax)
                        ->where('columna_fin', '>=', $columnaMin);
                });
            })
            // Cuando estamos editando un sector, no queremos que se compare consigo mismo.
            ->when($exceptSectorId, function ($query) use ($exceptSectorId) {
                $query->where('id', '<>', $exceptSectorId);
            })
            ->exists();
    }

    /**
     * Genera el array de asientos que corresponde al rectángulo del sector.
     */
    public function generarAsientos(Sector $sector): array
    {
        // Reutilizamos el cálculo de límites del sector ya guardado.
        // Así este método solo se encarga de construir la lista de asientos.
        $dimensiones = $this->calcularDimensiones($sector);

        // Si el sector no tiene límites válidos, no generamos nada.
        if ($dimensiones['cantidad_filas'] === 0 || $dimensiones['cantidad_columnas'] === 0) {
            return [];
        }

        // Aquí acumulamos todos los asientos que se van a insertar de una sola vez.
        $asientos = [];

        // Recorremos todas las filas del rectángulo, desde la inicial hasta la final.
        for ($fila = $dimensiones['fila_inicio']; $fila <= $dimensiones['fila_fin']; $fila++) {
            // Dentro de cada fila, recorremos todas las columnas del rango.
            for ($numero = $dimensiones['columna_inicio']; $numero <= $dimensiones['columna_fin']; $numero++) {
                // Cada combinación fila/columna representa un asiento concreto del sector.
                $asientos[] = [
                    'sector_id' => $sector->id,
                    'fila' => $fila,
                    'numero' => $numero,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $asientos;
    }

    /**
     * Calcula las dimensiones de un sector ya guardado.
     */
    public function calcularDimensiones(Sector $sector): array
    {
        // Leemos los límites guardados en la base de datos.
        // Si falta alguno, el sector todavía no está completamente definido.
        $fi = $sector->fila_inicio;
        $ff = $sector->fila_fin;
        $ci = $sector->columna_inicio;
        $cf = $sector->columna_fin;

        // Si no hay rectángulo definido, devolvemos un resultado neutro en vez de fallar.
        if (is_null($fi) || is_null($ff) || is_null($ci) || is_null($cf)) {
            return [
                'fila_inicio' => null,
                'fila_fin' => null,
                'columna_inicio' => null,
                'columna_fin' => null,
                'cantidad_filas' => 0,
                'cantidad_columnas' => 0,
                'total_asientos' => 0,
            ];
        }

        // Ordenamos los extremos para asegurar que el inicio siempre sea menor que el fin.
        $filaInicio = min((int) $fi, (int) $ff);
        $filaFin = max((int) $fi, (int) $ff);
        $columnaInicio = min((int) $ci, (int) $cf);
        $columnaFin = max((int) $ci, (int) $cf);

        // Calculamos la cantidad de filas y columnas usando un rango inclusivo.
        // Por eso sumamos 1: de 1 a 1 sigue habiendo 1 elemento.
        $cantidadFilas = max(0, ($filaFin - $filaInicio) + 1);
        $cantidadColumnas = max(0, ($columnaFin - $columnaInicio) + 1);

        // Devolvemos límites ya ordenados y dimensiones listas para usar en el resto del sistema.
        return [
            'fila_inicio' => $filaInicio,
            'fila_fin' => $filaFin,
            'columna_inicio' => $columnaInicio,
            'columna_fin' => $columnaFin,
            'cantidad_filas' => $cantidadFilas,
            'cantidad_columnas' => $cantidadColumnas,
            'total_asientos' => $cantidadFilas * $cantidadColumnas,
        ];
    }
}
