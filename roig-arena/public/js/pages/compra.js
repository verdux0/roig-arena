class BookingCanvasController {
    constructor(eventId) {
        this.eventId = eventId;

        // Datos globales
        this.eventPayload = null;
        this.seatCatalog = new Map();
        this.activeSelection = new Map();
        this.seatElementIndex = new Map();

        // Config SVG (debe coincidir con editarSectoresEvento.js)
        this.gridRows = 12;
        this.gridCols = 20;
        this.canvasWidth = 800;
        this.canvasHeight = 480;
        this.padLeft = 64;
        this.padTop = 42;
        this.padRight = 26;
        this.padBottom = 26;

        this.computeGeometry();

        // Reservas y pagos
        this.sectorPriceIndex = new Map();
        this.pendingReservations = [];
        this.paymentCountdownTimer = null;

        this.boot();
    }

    async boot() {
        try {
            console.log('[BookingCanvasController] Inicializando para evento:', this.eventId);

            // Cargar evento y sectores
            await this.fetchEventPayload();

            // Cargar todos los asientos
            await this.fetchSeatCatalog();

            // Renderizar mapa
            this.paintSeatCanvas();

            // Setup event listeners
            this.bindUiEvents();

            // Cargar carrito previo
            this.restoreDraftSelection();
            this.updateAuthState();
        } catch (error) {
            console.error('[BookingCanvasController] Error en init:', error);
            this.notifyError('Error cargando el mapa de asientos');
        }
    }

    notifyError(message) {
        alert(message);
        console.error(message);
    }

    buildAuthHeaders() {
        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        };
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
        return headers;
    }

    isAuthenticated() {
        return Boolean(localStorage.getItem('sanctum_token'));
    }

    updateAuthState() {
        const confirmBtn = document.getElementById('confirmBtn');
        const authNotice = document.getElementById('authNotice');
        const isAuthed = this.isAuthenticated();

        if (authNotice) {
            authNotice.style.display = isAuthed ? 'none' : 'block';
        }

        if (confirmBtn) {
            confirmBtn.disabled = this.activeSelection.size === 0;
        }
    }

    goToLogin() {
        const redirectTo = `${window.location.pathname}${window.location.search}`;
        window.location.href = `/login?redirect=${encodeURIComponent(redirectTo)}`;
    }

    async fetchEventPayload() {
        const response = await fetch(`/api/eventos/${this.eventId}`, {
            headers: this.buildAuthHeaders(),
            credentials: 'include'
        });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        this.eventPayload = data;

        console.log('[BookingCanvasController] Evento cargado:', this.eventPayload);
    }

    async fetchSeatCatalog() {
        const response = await fetch(`/api/eventos/${this.eventId}/asientos`, {
            headers: this.buildAuthHeaders(),
            credentials: 'include'
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const json = await response.json();
        const asientos = Array.isArray(json?.data?.asientos)
            ? json.data.asientos
            : Array.isArray(json?.data)
                ? json.data.flatMap(sector => Array.isArray(sector?.asientos) ? sector.asientos : [])
                : [];

        const totalFilas = Number(json?.data?.total_filas);
        const totalColumnas = Number(json?.data?.total_columnas);
        if (Number.isFinite(totalFilas) && totalFilas > 0) {
            this.gridRows = totalFilas;
        }
        if (Number.isFinite(totalColumnas) && totalColumnas > 0) {
            this.gridCols = totalColumnas;
        }
        this.computeGeometry();

        console.log(`[BookingCanvasController] Cargados ${asientos.length} asientos`);

        asientos.forEach(seat => {
            const key = String(seat.id);
            this.seatCatalog.set(key, {
                id: key,
                fila: seat.fila,
                numero: seat.numero,
                sector_id: seat.sector_id,
                sector_nombre: seat.sector_nombre || '',
                disponible: seat.disponible,
                estado: seat.disponible ? 'disponible' : 'ocupado',
                precio: this.eventPayload?.data?.sectores_disponibles?.find(s => s.id == seat.sector_id)?.pivot?.precio || 0
            });
        });
    }


    paintSeatCanvas() {
        const svg = document.getElementById('venueCanvas');
        if (!svg) {
            console.error('[BookingCanvasController] SVG no encontrado');
            return;
        }

        svg.innerHTML = '';
        this.seatElementIndex.clear();

        // GRID (líneas guía)
        this.drawGuideGrid(svg);

        // OVERLAY DE SECTORES primero, para que los asientos queden por encima y visibles.
        this.drawSectorLayers(svg);

        // ASIENTOS (como círculos clicables)
        this.drawSeatDots(svg);
    }

    drawGuideGrid(svg) {
        // Dibujar líneas horizontales y verticales de referencia
        // (similar a editarSectoresEvento.js)

        for (let row = 1; row <= this.gridRows; row++) {
            const y = this.padTop + (row - 1) * this.yStep;

            // Etiqueta de fila
            const rowLabel = this.buildSvgNode('text', {
                x: 34,
                y: y + 4,
                class: 'sector-map-axis-label',
                'text-anchor': 'middle'
            });
            rowLabel.textContent = String(row);
            svg.appendChild(rowLabel);

            // Línea horizontal
            this.appendSvgNode(svg, 'line', {
                x1: this.padLeft,
                y1: y,
                x2: this.padLeft + this.gridWidth,
                y2: y,
                class: 'sector-map-grid-line'
            });
        }

        for (let col = 1; col <= this.gridCols; col++) {
            const x = this.padLeft + (col - 1) * this.xStep;

            // Etiqueta de columna
            const colLabel = this.buildSvgNode('text', {
                x,
                y: this.canvasHeight - 6,
                class: 'sector-map-axis-label',
                'text-anchor': 'middle'
            });
            colLabel.textContent = String(col);
            svg.appendChild(colLabel);

            // Línea vertical
            this.appendSvgNode(svg, 'line', {
                x1: x,
                y1: this.padTop,
                x2: x,
                y2: this.padTop + this.gridHeight,
                class: 'sector-map-grid-line'
            });
        }
    }

    drawSeatDots(svg) {
        let count = 0;
        this.seatCatalog.forEach((asiento) => {
            const [fila, numero] = this.resolveSeatCoordinates(asiento);
            if (!Number.isFinite(fila) || !Number.isFinite(numero) || fila < 1 || numero < 1) {
                console.log('Asiento inválido:', asiento);
                return;
            }

            const x = this.padLeft + (numero - 1) * this.xStep;
            const y = this.padTop + (fila - 1) * this.yStep;

            if (count < 5) {
                console.log(`Asiento ${asiento.id}: fila ${fila}, numero ${numero}, x ${x}, y ${y}`);
                count++;
            }

            const seatGroup = this.buildSvgNode('g', {
                class: `seat-node seat-${asiento.estado}`,
                'data-seat-id': asiento.id,
                'data-fila': fila,
                'data-numero': numero,
                'aria-label': `Asiento fila ${fila} número ${numero}`,
                tabindex: asiento.estado === 'disponible' ? '0' : '-1',
                style: 'pointer-events: all;'
            });

            const seatSize = this.seatRadius * 2;
            const rect = this.buildSvgNode('rect', {
                x: x - seatSize / 2,
                y: y - seatSize / 2,
                width: seatSize,
                height: seatSize,
                rx: 3,
                class: 'seat-rect',
                style: 'pointer-events: all;'
            });

            const title = this.buildSvgNode('title', {});
            title.textContent = `Sector ${asiento.sector_nombre || 'N/A'} · Fila ${fila} · Asiento ${numero}`;
            seatGroup.appendChild(title);
            seatGroup.appendChild(rect);

            // Solo si está disponible, permitir click
            if (asiento.estado === 'disponible') {
                seatGroup.style.cursor = 'pointer';
                seatGroup.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.toggleSeat(asiento);
                });

                // Permitir seleccionar con teclado
                seatGroup.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        this.toggleSeat(asiento);
                    }
                });

            }

            svg.appendChild(seatGroup);
            this.seatElementIndex.set(asiento.id, seatGroup);
        });

        console.log(`[BookingCanvasController] Dibujados ${this.seatElementIndex.size} asientos, SVG children:`, svg.children.length);
    }

    drawSectorLayers(svg) {
        // Dibujar rectángulos semitransparentes sobre cada sector
        const sectores = this.eventPayload?.data?.sectores_disponibles ?? [];

        sectores.forEach(sector => {
            const bounds = this.resolveSectorBounds(sector);
            if (!bounds) {
                return;
            }

            const x1 = this.padLeft + (bounds.colInicio - 1) * this.xStep;
            const x2 = this.padLeft + (bounds.colFin - 1) * this.xStep;
            const y1 = this.padTop + (bounds.filaInicio - 1) * this.yStep;
            const y2 = this.padTop + (bounds.filaFin - 1) * this.yStep;

            const zonePadding = this.seatRadius + 3;
            const rectX = x1 - zonePadding;
            const rectY = y1 - zonePadding;
            const rectWidth = (x2 - x1) + zonePadding * 2;
            const rectHeight = (y2 - y1) + zonePadding * 2;

            const sectorRect = this.buildSvgNode('rect', {
                x: rectX,
                y: rectY,
                width: rectWidth,
                height: rectHeight,
                rx: 8,
                class: 'sector-zone-background',
                fill: sector.color_hex || '#5ba8ff',
                opacity: '0.15',
                'pointer-events': 'none'
            });

            svg.appendChild(sectorRect);

            // Etiqueta del sector
            const label = this.buildSvgNode('text', {
                x: rectX + 8,
                y: rectY + 16,
                class: 'sector-zone-label',
                'text-anchor': 'start',
                fill: sector.color_hex || '#5ba8ff',
                'font-size': '12px',
                'font-weight': 'bold',
                'pointer-events': 'none'
            });
            label.textContent = sector.nombre;
            svg.appendChild(label);
        });
    }

    // createSeatElement(asiento) {
    //     const x = this.padLeft + (asiento.numero - 1) * this.xStep;
    //     const y = this.padTop + (asiento.filaCoord - 1) * this.yStep;

    //     const seatGroup = this.buildSvgNode('g', {
    //         class: `seat-node seat-${asiento.estado}`,
    //         'data-seat-id': asiento.id,
    //         'data-sector-id': asiento.sector_id,
    //         'data-fila': asiento.fila,
    //         'data-numero': asiento.numero,
    //         tabindex: asiento.disponible ? '0' : '-1',
    //         'aria-label': `Fila ${asiento.fila}, Asiento ${asiento.numero}`
    //     });

    //     const seatCircle = this.buildSvgNode('circle', {
    //         cx: x,
    //         cy: y,
    //         r: this.seatRadius
    //     });

    //     const title = this.buildSvgNode('title', {});
    //     title.textContent = `${asiento.sector_nombre || 'Sector'} · Fila ${asiento.fila} · Asiento ${asiento.numero}`;

    //     seatGroup.appendChild(title);
    //     seatGroup.appendChild(seatCircle);

    //     if (asiento.disponible) {
    //         seatGroup.addEventListener('click', () => this.toggleSeat(asiento));
    //         seatGroup.addEventListener('keydown', event => {
    //             if (event.key === 'Enter' || event.key === ' ') {
    //                 event.preventDefault();
    //                 this.toggleSeat(asiento);
    //             }
    //         });
    //     }

    //     return seatGroup;
    // }

    resolveSectorBounds(sector) {
        const filaInicioRaw = this.normalizeRowToken(sector.fila_inicio);
        const filaFinRaw = this.normalizeRowToken(sector.fila_fin);
        const colInicioRaw = Number(sector.columna_inicio);
        const colFinRaw = Number(sector.columna_fin);

        if (!Number.isFinite(filaInicioRaw) || !Number.isFinite(filaFinRaw) || !Number.isFinite(colInicioRaw) || !Number.isFinite(colFinRaw)) {
            return null;
        }

        const filaInicio = Math.max(1, Math.min(this.gridRows, Math.min(filaInicioRaw, filaFinRaw)));
        const filaFin = Math.max(1, Math.min(this.gridRows, Math.max(filaInicioRaw, filaFinRaw)));
        const colInicio = Math.max(1, Math.min(this.gridCols, Math.min(colInicioRaw, colFinRaw)));
        const colFin = Math.max(1, Math.min(this.gridCols, Math.max(colInicioRaw, colFinRaw)));

        if (filaInicio > filaFin || colInicio > colFin) {
            return null;
        }

        return { filaInicio, filaFin, colInicio, colFin };
    }

    resolveSeatCoordinates(asiento) {
        // Convertir fila (número) y numero (columna) a coordenadas
        const fila = this.normalizeRowToken(asiento.fila);
        const numero = Number(asiento.numero);
        return [fila, numero];
    }

    normalizeRowToken(value) {
        // Convertir valor de fila (puede ser número o letra) a número
        if (typeof value === 'number') {
            return Number(value);
        }

        if (typeof value === 'string') {
            const normalized = value.trim().toUpperCase();
            if (!normalized) {
                return Number.NaN;
            }

            if (/^\d+$/.test(normalized)) {
                return Number(normalized);
            }

            // Soporta filas tipo A, B, ... Z, AA, AB, etc.
            if (/^[A-Z]+$/.test(normalized)) {
                let result = 0;
                for (const char of normalized) {
                    result = result * 26 + (char.charCodeAt(0) - 64);
                }
                return result;
            }

            return Number.NaN;
        }

        return Number(value);
    }

    computeGeometry() {
        this.gridWidth = this.canvasWidth - this.padLeft - this.padRight;
        this.gridHeight = this.canvasHeight - this.padTop - this.padBottom;
        this.seatRadius = Math.max(12, Math.min(20, Math.min(this.gridWidth / this.gridCols, this.gridHeight / this.gridRows) * 0.35));
        this.xStep = this.gridCols > 1 ? this.gridWidth / (this.gridCols - 1) : this.gridWidth;
        this.yStep = this.gridRows > 1 ? this.gridHeight / (this.gridRows - 1) : this.gridHeight;
    }


    // Utilities
    buildSvgNode(tag, attrs = {}) {
        const node = document.createElementNS('http://www.w3.org/2000/svg', tag);
        Object.entries(attrs).forEach(([key, value]) => {
            node.setAttribute(key, String(value));
        });
        return node;
    }

    appendSvgNode(parent, tag, attrs = {}) {
        const node = this.buildSvgNode(tag, attrs);
        parent.appendChild(node);
        return node;
    }

    toggleSeat(asiento) {
        const seatId = String(asiento.id);
        if (!asiento.disponible) {
            return;
        }

        if (this.activeSelection.has(seatId)) {
            // Desseleccionar
            this.activeSelection.delete(seatId);
        } else {
            // Seleccionar
            this.activeSelection.set(seatId, asiento);
        }

        // Actualizar visuales y carrito
        this.syncSeatVisualState();
        this.refreshCheckoutPanel();
        this.persistDraftSelection();
    }

    syncSeatVisualState() {
        this.seatElementIndex.forEach((seatNode, seatId) => {
            const shape = seatNode.querySelector('rect');

            seatNode.classList.remove('seat-selected');

            if (this.activeSelection.has(seatId)) {
                seatNode.classList.add('seat-selected');
                shape?.style.setProperty('--seat-state', 'selected');
            }
        });
    }

    refreshCheckoutPanel() {
        const seatCount = this.activeSelection.size;

        document.getElementById('seatCount').textContent = `${seatCount} asiento${seatCount !== 1 ? 's' : ''}`;

        this.renderSelectionSummary();
        this.renderTotalAmount();

        this.updateAuthState();
    }

    renderSelectionSummary() {
        const summary = document.getElementById('selectionSummary');

        if (this.activeSelection.size === 0) {
            summary.innerHTML = '<p class="empty-state">Selecciona asientos para comenzar</p>';
            return;
        }

        summary.innerHTML = '';

        this.activeSelection.forEach((asiento) => {
            const [fila, numero] = this.resolveSeatCoordinates(asiento);
            
            // Obtener precio del eventPayload correctamente
            const sector = this.eventPayload?.data?.sectores_disponibles?.find(s => s.id == asiento.sector_id);
            const precio = Number(sector?.pivot?.precio || 0);

            const item = document.createElement('div');
            item.className = 'selected-item';
            item.innerHTML = `
                <span class="selected-item-name">${asiento.sector_nombre || 'Sector'} - Fila ${fila}, Asiento ${numero}</span>
                <span class="selected-item-price">${precio.toFixed(2)}€</span>
                <button class="selected-item-remove" data-seat-id="${asiento.id}">✕</button>
            `;
            item.querySelector('.selected-item-remove').addEventListener('click', () => {
                this.toggleSeat(asiento);
            });

            summary.appendChild(item);
        });
    }

    renderPriceDetails() {
        const breakdown = document.getElementById('priceBreakdown');
        breakdown.innerHTML = '';

        const asientosPorSector = {};

        this.activeSelection.forEach(asiento => {
            const sectorId = asiento.sector_id;
            if (!asientosPorSector[sectorId]) {
                asientosPorSector[sectorId] = [];
            }
            asientosPorSector[sectorId].push(asiento);
        });

        let total = 0;
        Object.entries(asientosPorSector).forEach(([sectorId, asientos]) => {
            const sector = this.eventPayload.data.sectores_disponibles.find(s => s.id == sectorId);
            const precioSector = Number(sector?.pivot?.precio || 0);
            const subtotal = asientos.length * precioSector;
            total += subtotal;

            const line = document.createElement('div');
            line.className = 'price-line';
            line.innerHTML = `
                <span>${sector?.nombre || 'Sector '} (${asientos.length}x)</span>
                <strong>${subtotal.toFixed(2)}€</strong>
            `;
            breakdown.appendChild(line);
        });
    }

    calculateSelectionTotal() {
        let total = 0;

        this.activeSelection.forEach(asiento => {
            total += Number(asiento?.precio ?? 0);
        });

        return total;
    }

    renderTotalAmount() {
        const total = this.calculateSelectionTotal();
        const totalAmount = document.getElementById('totalAmount');

        if (totalAmount) {
            totalAmount.textContent = total.toFixed(2).replace('.', ',') + '€';
        }
    }

    persistDraftSelection() {
        const cartData = {
            eventId: this.eventId,
            seats: Array.from(this.activeSelection.values())
        };
        localStorage.setItem('booking_draft_v2', JSON.stringify(cartData));
    }

    restoreDraftSelection() {
        const stored = localStorage.getItem('booking_draft_v2');
        if (!stored) {
            return;
        }

        try {
            const parsed = JSON.parse(stored);
            if (parsed.eventId !== this.eventId) {
                localStorage.removeItem('booking_draft_v2');
                return;
            }

            parsed.seats?.forEach(asiento => {
                const seatId = String(asiento.id);
                if (this.seatCatalog.has(seatId)) {
                    const existing = this.seatCatalog.get(seatId);
                    if (existing && existing.disponible) {
                        this.activeSelection.set(seatId, existing);
                    }
                }
            });

            this.syncSeatVisualState();
            this.refreshCheckoutPanel();
        } catch (error) {
            console.error('Error cargando carrito desde localStorage:', error);
            localStorage.removeItem('booking_draft_v2');
        }
    }

    bindUiEvents() {
        const confirmBtn = document.getElementById('confirmBtn');
        const loginLink = document.getElementById('loginLink');

        if (loginLink) {
            const redirectTo = `${window.location.pathname}${window.location.search}`;
            loginLink.href = `/login?redirect=${encodeURIComponent(redirectTo)}`;
        }

        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.startReservationFlow());
        }
        document.getElementById('payBtn').addEventListener('click', () => this.finalizePayment());
        document.getElementById('closePaymentModal').addEventListener('click', () => this.hidePaymentModal());
    }

    async startReservationFlow() {
        if (!this.isAuthenticated()) {
            this.updateAuthState();
            alert('Necesitas iniciar sesión para comprar asientos.');
            this.goToLogin();
            return;
        }

        if (this.activeSelection.size === 0) {
            alert('Selecciona al menos un asiento para continuar.');
            return;
        }

        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Authorization: token ? `Bearer ${token}` : ''
        };

        const asientos = Array.from(this.activeSelection.values()).map(asiento => ({
            evento_id: Number(this.eventId),
            asiento_id: Number(asiento.id)
        }));

        this.pendingReservations = [];

        try {
            for (const asiento of asientos) {
                const response = await fetch('/api/reservas', {
                    method: 'POST',
                    headers,
                    credentials: 'include',
                    body: JSON.stringify(asiento)
                });

                if (response.status === 401 || response.status === 302) {
                    this.goToLogin();
                    return;
                }

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor inesperada (HTTP ${response.status}):\n${text.slice(0, 200)}`);
                }

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || `HTTP ${response.status}`);
                }

                this.pendingReservations.push(data.data);
            }

            if (this.pendingReservations.length > 0) {
                this.showPaymentModal();
            }
        } catch (error) {
            console.error('Error al reservar los asientos:', error);
            alert('Error al reservar los asientos: ' + error.message);
        }
    }

    showPaymentModal() {
        const modal = document.getElementById('paymentModal');
        const summary = document.getElementById('paymentSummary');
        const totalEl = document.getElementById('paymentTotal');
        const payBtn = document.getElementById('payBtn');

        if (!modal || !summary || !totalEl || !payBtn) {
            return;
        }

        payBtn.disabled = false;
        payBtn.textContent = 'Pagar ahora';

        summary.innerHTML = '';
        let total = 0;

        Array.from(this.activeSelection.values()).forEach(seat => {
            const precio = Number(seat?.precio ?? 0);
            total += precio;
            const row = document.createElement('div');
            row.className = 'payment-seat-row';
            row.innerHTML = `<span>Fila ${seat.fila} · Asiento ${seat.numero} · ${seat.sector_nombre || ''}</span><span>${precio.toFixed(2)}€</span>`;
            summary.appendChild(row);
        });

        totalEl.textContent = total.toFixed(2).replace('.', ',') + '€';

        const primeraReserva = this.pendingReservations[0];
        const expira = primeraReserva?.reservado_hasta
            ? new Date(primeraReserva.reservado_hasta)
            : new Date(Date.now() + 15 * 60 * 1000);

        this.startPaymentCountdown(expira);

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    startPaymentCountdown(expiresAt) {
        clearInterval(this.paymentCountdownTimer);
        const el = document.getElementById('paymentCountdown');
        if (!el) {
            return;
        }

        const tick = () => {
            const remaining = Math.max(0, expiresAt - Date.now());
            const mins = Math.floor(remaining / 60000);
            const secs = Math.floor((remaining % 60000) / 1000);
            el.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

            if (remaining <= 0) {
                clearInterval(this.paymentCountdownTimer);
                el.textContent = '00:00';
                const payBtn = document.getElementById('payBtn');
                if (payBtn) {
                    payBtn.disabled = true;
                }
                alert('El tiempo de reserva ha expirado. Por favor, vuelve a seleccionar tus asientos.');
                this.handleExpiredReservations();
            }
        };

        tick();
        this.paymentCountdownTimer = setInterval(tick, 1000);
    }

    async releasePendingReservations() {
        if (!Array.isArray(this.pendingReservations) || this.pendingReservations.length === 0) {
            return;
        }

        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Authorization: token ? `Bearer ${token}` : ''
        };

        const requests = this.pendingReservations
            .filter(reserva => Number.isFinite(Number(reserva?.id)))
            .map(reserva => fetch(`/api/reservas/${reserva.id}`, {
                method: 'DELETE',
                headers,
                credentials: 'include'
            }));

        await Promise.allSettled(requests);
        this.pendingReservations = [];
    }

    async handleExpiredReservations() {
        try {
            await this.releasePendingReservations();
        } catch (error) {
            console.error('Error cancelando reservas expiradas:', error);
        }

        this.activeSelection.clear();
        localStorage.removeItem('booking_draft_v2');
        this.syncSeatVisualState();
        this.refreshCheckoutPanel();
        this.hidePaymentModal();
        this.paintSeatCanvas();
    }

    hidePaymentModal() {
        clearInterval(this.paymentCountdownTimer);
        const modal = document.getElementById('paymentModal');
        if (modal) {
            modal.style.display = 'none';
        }
        document.body.style.overflow = '';
    }

    async finalizePayment() {
        const payBtn = document.getElementById('payBtn');
        if (payBtn) {
            payBtn.disabled = true;
            payBtn.textContent = 'Procesando...';
        }

        const totalPaid = this.calculateSelectionTotal();

        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Authorization: token ? `Bearer ${token}` : ''
        };

        try {
            const response = await fetch('/api/compras/confirmar', {
                method: 'POST',
                headers,
                credentials: 'include',
                body: JSON.stringify({ metodo_pago: 'tarjeta' })
            });

            if (response.status === 401 || response.status === 302) {
                this.goToLogin();
                return;
            }

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Respuesta del servidor inesperada (HTTP ${response.status}):\n${text.slice(0, 200)}`);
            }

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            clearInterval(this.paymentCountdownTimer);
            localStorage.removeItem('booking_draft_v2');
            this.activeSelection.clear();
            this.pendingReservations = [];
            this.syncSeatVisualState();
            this.refreshCheckoutPanel();
            this.hidePaymentModal();

            alert(`¡Compra confirmada! Total: ${totalPaid.toFixed(2)}€`);
            window.location.href = '/eventos';
        } catch (error) {
            console.error('Error al confirmar pago:', error);
            alert('Error al procesar el pago: ' + error.message);
            if (payBtn) {
                payBtn.disabled = false;
                payBtn.textContent = 'Pagar ahora';
            }
        }
    }
}

// Inicializar cuando la página carga
window.addEventListener('DOMContentLoaded', () => {
    const eventId = document.querySelector('[data-evento-id]')?.dataset.eventoId;
    if (eventId) {
        window.bookingCanvasController = new BookingCanvasController(eventId);
    }
});
