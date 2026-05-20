@extends('layouts.app')

@section('title', 'Comprar Entradas | ' . $evento->nombre . ' | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/compra.css">
@endsection

@section('content')
    <!-- Cabecera del flujo de compra -->
    <div class="compra-header">
        <h1>{{ $evento->nombre }}</h1>
        <p>
            @if($evento->fecha)
                {{ $evento->fecha->format('d/m/Y') }}
                @if($evento->hora)
                    · {{ $evento->hora->format('H:i') }}
                @endif
            @endif
        </p>
    </div>

    <!-- Estructura principal -->
    <div class="booking-layout">
        <!-- Panel izquierdo: selector de asientos -->
        <div class="booking-stage">
            <h2>Selecciona tus asientos</h2>

            <div class="stage-canvas-shell" aria-hidden="true">
                <svg id="stageCanvas"
                    class="stage-canvas"
                    viewBox="0 0 800 50"
                    width="800"
                    height="50"
                    role="img"
                    aria-label="Escenario">
                    <rect x="20" y="10" width="760" height="24" rx="12" class="sector-map-stage"></rect>
                    <text x="400" y="27" class="sector-map-stage-label" text-anchor="middle">ESCENARIO</text>
                </svg>
            </div>

            <!-- Referencia visual de estados -->
            <div class="legend">
                <span class="legend-item">
                    <div class="seat seat-available"></div> Disponible
                </span>
                <span class="legend-item">
                    <div class="seat seat-reserved"></div> Ocupado
                </span>
                <span class="legend-item">
                    <div class="seat seat-selected"></div> Seleccionado
                </span>
            </div>

            <!-- Lienzo SVG interactivo -->
            <div class="venue-canvas-shell">
                <svg id="venueCanvas"
                    class="venue-canvas"
                    viewBox="0 0 800 480"
                    width="800"
                    height="480"
                    role="img"
                    aria-label="Mapa interactivo de asientos del evento">
                </svg>
            </div>

            <!-- Info del sector actual (opcional) -->
            <div id="sectorInfo" class="sector-info" style="display:none;">
                <h3 id="sectorTitle"></h3>
                <p id="sectorDesc"></p>
            </div>
        </div>

        <!-- Panel derecho: resumen de compra -->
        <aside class="checkout-sidebar">
            <div class="checkout-header">
                <h3>Tu Carrito</h3>
                <span class="seat-count" id="seatCount">0 asientos</span>
            </div>

            <div class="checkout-content">
                <!-- Resumen de selección -->
                <div class="selection-summary" id="selectionSummary">
                    <p class="empty-state">Selecciona asientos para comenzar</p>
                </div>

                <!-- Total -->
                <div class="total-section">
                    <p class="total-label">Total a pagar:</p>
                    <p class="total-amount" id="totalAmount">0,00€</p>
                </div>

                <div id="authNotice" class="auth-notice" style="display:none;">
                    Necesitas iniciar sesión para comprar.
                    <a id="loginLink" href="/login">Iniciar sesión</a>
                </div>

                <!-- Botones de acción -->
                <div class="checkout-actions">
                    <button class="btn btn-primary" id="confirmBtn">
                        Confirmar Compra
                    </button>
                    <a href="{{ route('eventos.show', ['evento' => $evento->id], false) }}" class="btn btn-secondary">
                        Volver
                    </a>
                </div>
            </div>
        </aside>
    </div>

    <div id="eventoData" data-evento-id="{{ $evento->id }}"></div>

    {{-- Modal de confirmación de pago --}}
    <div id="paymentModal" class="payment-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
        <div class="payment-modal">
            {{-- Cabecera --}}
            <div class="payment-modal-header">
                <h2 id="paymentModalTitle">Simulación de Pago</h2>
                <button id="closePaymentModal" class="payment-modal-close" aria-label="Cerrar">&times;</button>
            </div>

            {{-- Temporizador de reserva --}}
            <div class="payment-timer">
                <span>Tus asientos están reservados durante: </span>
                <strong id="paymentCountdown">01:00</strong>
            </div>

            {{-- Resumen de asientos (rellenado por JS) --}}
            <div id="paymentSummary" class="payment-summary"></div>

            {{-- Formulario simulado de pago (no funcional) --}}
            <div class="payment-form">
                <h3>Datos de tarjeta</h3>
                <div class="payment-field">
                    <label for="cardNumber">Número de tarjeta</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="payment-field-row">
                    <div class="payment-field">
                        <label for="cardExpiry">Caducidad</label>
                        <input type="text" id="cardExpiry" placeholder="MM/AA" maxlength="5">
                    </div>
                    <div class="payment-field">
                        <label for="cardCvv">CVV</label>
                        <input type="text" id="cardCvv" placeholder="123" maxlength="3">
                    </div>
                </div>
                <div class="payment-field">
                    <label for="cardName">Titular</label>
                    <input type="text" id="cardName" placeholder="Nombre en la tarjeta">
                </div>
            </div>

            {{-- Total y acción de pago --}}
            <div class="payment-modal-footer">
                <p class="payment-total">Total: <strong id="paymentTotal">0,00€</strong></p>
                <button id="payBtn" class="btn btn-primary payment-pay-btn">Pagar ahora</button>
            </div>
        </div>
    </div>

    <script src="/js/pages/compra.js"></script>
@endsection
