@extends('layouts.app')

@section('title', 'Mi Evento Detallado | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
@endsection

@section('content')
    <div class="eventos-header">
        <h1>Mi Evento Detallado</h1>
        <p class="muted no-margin">Aquí puedes ver tus entradas en formato lista, con los datos del evento y tu asiento.</p>
    </div>

    <section class="ticket-list grid-gap-bottom">
        @php $hayEntradas = false; @endphp

        @foreach ($evento->entradas as $entrada)
            @php
                $hayEntradas = true;
                $asiento = $entrada->asiento;
                $nombreAsiento = $asiento && $asiento->sector
                    ? $asiento->sector->nombre . ' - Fila ' . $asiento->fila . ' - Asiento ' . $asiento->numero
                    : 'Asiento no disponible';
            @endphp

            <article class="card ticket-card">
                <div class="ticket-main">
                    <h2 class="ticket-event-name">{{ $evento->nombre }}</h2>
                    <p class="muted no-margin">
                        Fecha: {{ $evento->fecha ? $evento->fecha->format('d/m/Y') : 'Por confirmar' }}
                        · Hora: {{ $evento->hora ? $evento->hora->format('H:i') : 'Por confirmar' }}
                    </p>
                    <p class="ticket-seat no-margin">{{ $nombreAsiento }}</p>
                </div>

                <div class="ticket-side">
                    <p class="muted no-margin">Entrada #{{ $entrada->id }}</p>
                    <p class="muted no-margin">Precio: {{ number_format((float) $entrada->precio_pagado, 2, ',', '.') }} €</p>
                    <div class="ticket-actions">
                        <button
                            type="button"
                            class="btn btn-sm ticket-download-btn"
                            data-ticket-download
                            data-evento="{{ $evento->nombre }}"
                            data-fecha="{{ $evento->fecha ? $evento->fecha->format('d/m/Y') : 'Por confirmar' }}"
                            data-hora="{{ $evento->hora ? $evento->hora->format('H:i') : 'Por confirmar' }}"
                            data-asiento="{{ $nombreAsiento }}"
                            data-entrada="{{ $entrada->id }}"
                            data-precio="{{ number_format((float) $entrada->precio_pagado, 2, ',', '.') }} €"
                            data-codigo="{{ $entrada->codigo_qr }}"
                        >
                            Descargar PDF
                        </button>
                        @if (!$entrada->descargada)
                            <button
                                type="button"
                                class="btn btn-sm ticket-cancel-btn"
                                data-ticket-cancel
                                data-entrada="{{ $entrada->id }}"
                            >
                                Cancelar compra
                            </button>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach

        @if(!$hayEntradas)
            <article class="card">
                <p class="no-margin">No tienes entradas disponibles.</p>
            </article>
        @endif
    </section>
@endsection

@section('page_scripts')
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="/js/pages/downloadpdf.js"></script>
@endsection
