@extends('layouts.app')

@section('title', 'Editor de sectores | ' . $evento->nombre)

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
    <link rel="stylesheet" href="/css/pages/show.css">
    <link rel="stylesheet" href="/css/pages/sectores-editor.css">
@endsection

@section('content')
    <section class="container py-4">
        <div class="mb-4">
            <a href="{{ route('eventos.show', $evento, false) }}" class="btn btn-alt btn-sm">Volver al evento</a>
        </div>

        <div class="card card--dark p-4">
            <header class="mb-4">
                <h1 class="mb-2">Editor de sectores</h1>
                <p class="mb-0 text-muted">
                    Evento: <strong>{{ $evento->nombre }}</strong> · ID: <strong>{{ $eventoId }}</strong>
                </p>
            </header>

            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <div class="sector-map-shell p-3 p-md-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <p class="mb-0"><strong>Mapa base de asientos</strong></p>
                            <small class="text-muted">Selecciona asiento inicial y final</small>
                        </div>

                        @php
                            $sectoresMapData = $sectoresIniciales->map(function ($sector) {
                                return [
                                    'id' => $sector->id,
                                    'nombre' => $sector->nombre,
                                    'color_hex' => $sector->color_hex ?: '#5ba8ff',
                                    'fila_inicio' => $sector->fila_inicio,
                                    'fila_fin' => $sector->fila_fin,
                                    'columna_inicio' => $sector->columna_inicio,
                                    'columna_fin' => $sector->columna_fin,
                                ];
                            })->values();
                        @endphp

                        <div id="sector-map-editor"
                            class="sector-map-editor"
                            data-seat-rows="12"
                            data-seat-cols="20"
                            data-evento-id="{{ $eventoId }}"
                            data-sectors='@json($sectoresMapData)'>
                            <svg id="sector-map-svg"
                                class="sector-map-svg"
                                viewBox="0 0 960 560"
                                role="img"
                                aria-label="Mapa de asientos clicables para crear sectores"></svg>

                            <!-- Popup de acciones para sectores (borrar / editar) -->
                            <div id="sector-action-popup" class="sector-action-popup" hidden>
                                <button type="button" class="btn btn-outline-danger btn-sm" data-sector-delete>Borrar</button>
                                <button type="button" class="btn btn-primary btn-sm" data-sector-edit>Editar</button>
                            </div>
                        </div>

                        <div class="sector-selection-summary mt-3">
                            <span>Inicio: <strong data-selection-start>-</strong></span>
                            <span>Fin: <strong data-selection-end>-</strong></span>
                            <span>Area: <strong data-selection-size>-</strong></span>
                        </div>
                    </div>
                </div>

                {{-- <aside class="col-12 col-md-7 col-xl-3">
                    <div class="sector-side-panel p-4 h-100">
                        <p class="mb-3"><strong>Sectores iniciales</strong></p>

                        @if($sectoresIniciales->isEmpty())
                            <p class="text-muted mb-0">Este evento todavia no tiene sectores asignados.</p>
                        @else
                            <ul class="list-unstyled mb-0">
                                @foreach($sectoresIniciales as $sector)
                                    <li class="mb-2">
                                        <strong>{{ $sector->nombre }}</strong>
                                        <div class="text-muted small">
                                            Filas: {{ $sector->fila_inicio ?? '-' }} - {{ $sector->fila_fin ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            Columnas: {{ $sector->columna_inicio ?? '-' }} - {{ $sector->columna_fin ?? '-' }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </aside> --}}

                <aside class="col-12 col-md-5 col-xl-2">
                    <div class="sector-side-panel p-4 h-100 d-flex flex-column gap-2">
                        <p class="mb-1"><strong>Acciones</strong></p>
                        <p class="text-muted small mb-3">Base visual lista para conectar con API.</p>

                        <button type="button" class="btn btn-primary btn-sm w-100" data-save-sector>Guardar sector</button>
                        <button type="button" class="btn btn-alt btn-sm w-100" data-clear-selection>Limpiar seleccion</button>
                        <button type="button" class="btn btn-outline-light btn-sm w-100">Vista previa</button>

                        <div class="mt-auto pt-3 border-top border-secondary-subtle">
                            <small class="text-muted d-block">Estado: sin guardar</small>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection

@section('page_scripts')
    <script src="/js/pages/editarSectoresEvento.js"></script>
@endsection
