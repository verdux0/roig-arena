@extends('layouts.app')

@section('title', 'Pagos Pendientes | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/pagos-pendientes.css">
@endsection

@section('content')
    <div class="pagos-header">
        <h1>Pagos Pendientes</h1>
        <p class="muted no-margin">Completa el pago de tus entradas antes de que expire la reserva.</p>
    </div>

    @if ($pagosPendientes->isEmpty())
        <section class="card">
            <article class="card-body">
                <p class="no-margin">✓ No tienes pagos pendientes. Todas tus entradas están pagadas.</p>
            </article>
        </section>
    @else
        <section class="pagos-container">
            @foreach ($pagosPendientes as $pago)
                <article class="pago-evento-card" data-evento-id="{{ $pago['evento']->id }}">
                    {{-- Información del evento --}}
                    <div class="pago-evento-header">
                        <div class="pago-evento-info">
                            <h2 class="pago-evento-nombre">{{ $pago['evento']->nombre }}</h2>
                            <p class="pago-evento-meta">
                                <span class="pago-fecha">
                                    📅 {{ optional($pago['evento']->fecha)->format('d/m/Y') }}
                                    @if($pago['evento']->hora) · {{ optional($pago['evento']->hora)->format('H:i') }} @endif
                                </span>
                            </p>
                        </div>
                        <div class="pago-evento-monto">
                            <strong class="pago-monto-text">{{ number_format($pago['montoPendiente'], 2, ',', '.') }} €</strong>
                            <small>{{ $pago['total'] }} {{ $pago['total'] == 1 ? 'entrada' : 'entradas' }}</small>
                        </div>
                    </div>

                    {{-- Listado de entradas por pagar --}}
                    <div class="pago-entradas-list">
                        <h3 class="pago-entradas-title">Entradas sin pagar</h3>
                        <ul class="entradas-ul">
                            @foreach ($pago['reservas'] as $reserva)
                                <li class="entrada-item" data-reserva-id="{{ $reserva['id'] }}">
                                    <div class="entrada-info">
                                        <strong class="entrada-asiento">
                                            {{ $reserva['asiento']['fila'] }}{{ $reserva['asiento']['numero'] }}
                                        </strong>
                                        <span class="entrada-sector">{{ $reserva['asiento']['sector']['nombre'] }}</span>
                                    </div>
                                    <div class="entrada-precio">
                                        {{ number_format($reserva['precio_asiento'], 2, ',', '.') }} €
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    {{-- Botón para abrir modal de pago --}}
                    <div class="pago-evento-footer">
                        <button class="btn btn-primary btn-pagar"
                                onclick='abrirModalPago(event, {{ $pago['evento']->id }}, @json($pago['reservas']), {{ $pago['montoPendiente'] }}, "{{ $pago['reservado_hasta'] }}")'>
                            Pagar Ahora
                        </button>
                    </div>
                </article>
            @endforeach
        </section>
    @endif

    {{-- Modal de pago flotante --}}
    <div id="paymentModal" class="payment-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
        <div class="payment-modal">
            {{-- Cabecera --}}
            <div class="payment-modal-header">
                <h2 id="paymentModalTitle">Procesar Pago</h2>
                <button id="closePaymentModal" class="payment-modal-close" aria-label="Cerrar">&times;</button>
            </div>

            {{-- Temporizador reserva --}}
            <div class="payment-timer" id="paymentTimerContainer" style="display:none;">
                <span>Tu pago expira en: </span>
                <strong id="paymentCountdown">10:00</strong>
            </div>

            {{-- Resumen de evento y entradas --}}
            <div class="payment-summary" id="paymentSummary">
                <h3 id="eventoNombreModal"></h3>
                <p id="eventoFechaModal"></p>
                <div id="entradasSummary"></div>
            </div>

            {{-- Formulario de pago --}}
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

            {{-- Total y botón de pago --}}
            <div class="payment-modal-footer">
                <p class="payment-total">Total: <strong id="paymentTotal">0,00€</strong></p>
                <button id="payBtn" class="btn btn-primary payment-pay-btn" onclick="procesarPago()">Pagar ahora</button>
            </div>
        </div>
    </div>

    <script>
        let currentEventId = null;
        let currentReservas = [];
        let currentMonto = 0;
        let currentReservadoHasta = null;

        /**
         * Abre el modal de pago para un evento específico
         */
        function abrirModalPago(event, eventoId, reservas, monto, reservadoHasta) {
            event.preventDefault();
            currentEventId = eventoId;
            currentReservas = reservas;
            currentMonto = monto;
            currentReservadoHasta = reservadoHasta;

            // Obtener datos del evento desde el card
            const card = document.querySelector(`[data-evento-id="${eventoId}"]`);
            const eventoNombre = card.querySelector('.pago-evento-nombre').textContent;
            const eventoFecha = card.querySelector('.pago-fecha').textContent;

            // Llenar el modal
            document.getElementById('eventoNombreModal').textContent = eventoNombre;
            document.getElementById('eventoFechaModal').textContent = eventoFecha;

            // Generar resumen de entradas
            let resumen = '<div class="entradas-modal-list">';
            reservas.forEach(reserva => {
                const asientoInfo = `${reserva.asiento.fila}${reserva.asiento.numero}`;
                const sectorInfo = reserva.asiento.sector.nombre;
                const precio = (reserva.precio_asiento || 0).toLocaleString('es-ES', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
                resumen += `
                    <div class="entrada-modal-item">
                        <span>${asientoInfo} - ${sectorInfo}</span>
                        <strong>${precio}€</strong>
                    </div>
                `;
            });
            resumen += '</div>';

            document.getElementById('entradasSummary').innerHTML = resumen;

            // Actualizar total
            const montoFormato = monto.toLocaleString('es-ES', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            document.getElementById('paymentTotal').textContent = `${montoFormato}€`;

            // Mostrar modal
            document.getElementById('paymentModal').style.display = 'flex';

            // Iniciar contador real basado en reservado_hasta (sigue corriendo aunque cierres modal)
            iniciarCountdown(reservadoHasta);
        }

        /**
         * Cierra el modal de pago
         */
        function cerrarModalPago() {
            document.getElementById('paymentModal').style.display = 'none';
            clearInterval(window.countdownInterval);
        }

        /**
         * Inicia el countdown real contra la fecha de expiración de la reserva
         */
        function iniciarCountdown(reservadoHasta) {
            clearInterval(window.countdownInterval);

            document.getElementById('paymentTimerContainer').style.display = 'block';

            function actualizarContador() {
                const ahora = new Date().getTime();
                const expiraEn = new Date(reservadoHasta).getTime();
                const diferenciaMs = expiraEn - ahora;

                if (diferenciaMs <= 0) {
                    document.getElementById('paymentCountdown').textContent = '00:00';
                    clearInterval(window.countdownInterval);
                    alert('El tiempo para pagar ha expirado');
                    cerrarModalPago();
                    location.reload();
                    return;
                }

                const totalSegundos = Math.floor(diferenciaMs / 1000);
                const minutos = Math.floor(totalSegundos / 60);
                const segundos = totalSegundos % 60;
                const formato = `${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;

                document.getElementById('paymentCountdown').textContent = formato;
            }

            actualizarContador();
            window.countdownInterval = setInterval(actualizarContador, 1000);
        }

        /**
         * Procesa el pago
         */
        function procesarPago() {
            // Validación básica
            if (!document.getElementById('cardNumber').value) {
                alert('Por favor ingresa los datos de la tarjeta');
                return;
            }

            const reservasIds = currentReservas.map(r => r.id);

            fetch('/mis-pagos-pendientes/pagar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    reservas_ids: reservasIds,
                    metodo_pago: 'tarjeta'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pago procesado exitosamente');
                    cerrarModalPago();
                    location.reload(); // Recargar para actualizar la lista
                } else {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar el pago');
            });
        }

        // Event listeners
        document.getElementById('closePaymentModal')?.addEventListener('click', cerrarModalPago);

        // Cerrar modal al hacer click fuera
        document.getElementById('paymentModal')?.addEventListener('click', (e) => {
            if (e.target.id === 'paymentModal') {
                cerrarModalPago();
            }
        });

        // Formato automático de tarjeta
        document.getElementById('cardNumber')?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').replace(/(\d{4})/g, '$1 ').trim();
        });

        // Formato automático de fecha
        document.getElementById('cardExpiry')?.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Solo números en CVV
        document.getElementById('cardCvv')?.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    </script>
@endsection
