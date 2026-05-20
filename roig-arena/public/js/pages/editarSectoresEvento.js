(function () {
    function initSectorMapEditor() {
        // Busca los elementos base del editor en el DOM.
        const container = document.getElementById('sector-map-editor');
        const svg = document.getElementById('sector-map-svg');

        // Si la vista no tiene el editor, salimos sin hacer nada.
        if (!container || !svg) {
            return;
        }

        // Lee la configuracion de filas/columnas desde atributos data-*.
        const rows = Number(container.dataset.seatRows || 12);
        const cols = Number(container.dataset.seatCols || 20);
        const sectors = (function () {
            // Sectores enviados desde Blade para pintar su zona de color.
            // Formato esperado por item:
            // { nombre, color_hex, fila_inicio, fila_fin, columna_inicio, columna_fin }
            try {
                return JSON.parse(container.dataset.sectors || '[]');
            } catch (error) {
                // Si el JSON llega malformado, evitamos romper toda la pantalla.
                return [];
            }
        })();

        // Referencias a elementos que muestran el resumen de seleccion.
        const startEl = document.querySelector('[data-selection-start]');
        const endEl = document.querySelector('[data-selection-end]');
        const sizeEl = document.querySelector('[data-selection-size]');
        const clearButton = document.querySelector('[data-clear-selection]');

        // Medidas del lienzo SVG y margenes internos para dibujar la rejilla.
        const viewWidth = 960;
        const viewHeight = 560;
        const padLeft = 64;
        const padTop = 42;
        const padRight = 26;
        const padBottom = 26;
        const gridWidth = viewWidth - padLeft - padRight;
        const gridHeight = viewHeight - padTop - padBottom;

        // Calcula tamano de asiento y separacion entre puntos segun dimensiones.
        const seatRadius = Math.max(6, Math.min(13, Math.min(gridWidth / cols, gridHeight / rows) * 0.28));
        const xStep = gridWidth / (cols - 1);
        const yStep = gridHeight / (rows - 1);

        // Estado del editor: punto inicial, punto final y mapa de nodos SVG.
        const state = {
            start: null,
            end: null,
            seats: new Map(),
        };

        // Utilidad para generar una clave unica por asiento (fila-columna).
        function seatKey(fila, columna) {
            // Clave unica por asiento para guardar/consultar en el Map.
            return fila + '-' + columna;
        }

        // Que hace esto: Crea un nodo SVG con atributos dados, para evitar repetir codigo al crear elementos.
        function createSvgNode(tag, attrs) {
            // Crea nodos SVG y aplica sus atributos en una sola utilidad.
            const node = document.createElementNS('http://www.w3.org/2000/svg', tag);
            Object.entries(attrs).forEach(([key, value]) => {
                node.setAttribute(key, String(value));
            });
            return node;
        }

        // Para no repetir conversion a numero entero en varias partes del codigo, centralizamos la logica de conversion y validacion en esta funcion.
        function toInt(value) {
            // Convierte a numero entero de forma segura.
            const n = Number(value);
            return Number.isFinite(n) ? n : null;
        }

        function normalizeSectorBounds(sector) {
            // Ordena y limita los bordes del sector al tamano real de la rejilla.
            // Esto permite que funcionen sectores definidos en cualquier direccion
            // (inicio > fin) y evita salirse de filas/columnas disponibles.
            const filaInicioRaw = toInt(sector.fila_inicio);
            const filaFinRaw = toInt(sector.fila_fin);
            const colInicioRaw = toInt(sector.columna_inicio);
            const colFinRaw = toInt(sector.columna_fin);

            if (
                filaInicioRaw === null ||
                filaFinRaw === null ||
                colInicioRaw === null ||
                colFinRaw === null
            ) {
                return null;
            }

            const filaInicio = Math.max(1, Math.min(rows, Math.min(filaInicioRaw, filaFinRaw)));
            const filaFin = Math.max(1, Math.min(rows, Math.max(filaInicioRaw, filaFinRaw)));
            const colInicio = Math.max(1, Math.min(cols, Math.min(colInicioRaw, colFinRaw)));
            const colFin = Math.max(1, Math.min(cols, Math.max(colInicioRaw, colFinRaw)));

            // Si tras normalizar no queda un rango valido, el sector se ignora.
            if (filaInicio > filaFin || colInicio > colFin) {
                return null;
            }

            return { filaInicio, filaFin, colInicio, colFin };
        }

        function drawSectorBackgrounds() {
            // Dibuja los sectores en primer plano (encima de asientos).
            // Eliminamos cualquier rect anterior para evitar duplicados.
            const previous = svg.querySelectorAll('.sector-zone-foreground, .sector-zone-label');
            previous.forEach(n => n.remove());

            sectors.forEach((sector) => {
                const bounds = normalizeSectorBounds(sector);
                if (!bounds) {
                    return;
                }

                const x1 = padLeft + (bounds.colInicio - 1) * xStep;
                const x2 = padLeft + (bounds.colFin - 1) * xStep;
                const y1 = padTop + (bounds.filaInicio - 1) * yStep;
                const y2 = padTop + (bounds.filaFin - 1) * yStep;

                const zonePadding = seatRadius + 3;
                const rectX = x1 - zonePadding;
                const rectY = y1 - zonePadding;
                const rectWidth = (x2 - x1) + zonePadding * 2;
                const rectHeight = (y2 - y1) + zonePadding * 2;
                const color = sector.color_hex || '#5ba8ff';

                // Rectangulo en primer plano que captura clicks.
                const sectorRect = createSvgNode('rect', {
                    x: rectX,
                    y: rectY,
                    width: rectWidth,
                    height: rectHeight,
                    rx: 8,
                    class: 'sector-zone-foreground',
                    fill: color,
                    stroke: color,
                });
                // Aseguramos que reciba eventos de puntero.
                sectorRect.setAttribute('pointer-events', 'auto');
                sectorRect.dataset.sectorId = sector.id || '';

                // Etiqueta encima del rectangulo.
                const sectorLabel = createSvgNode('text', {
                    x: rectX + 8,
                    y: rectY + 14,
                    class: 'sector-zone-label',
                    'text-anchor': 'start',
                });
                sectorLabel.textContent = sector.nombre || 'Sector';

                // Marcamos los asientos que estan dentro del sector para deshabilitar su click.
                for (let r = bounds.filaInicio; r <= bounds.filaFin; r++) {
                    for (let c = bounds.colInicio; c <= bounds.colFin; c++) {
                        const key = seatKey(r, c);
                        const seatNode = state.seats.get(key);
                        if (seatNode) {
                            seatNode.classList.add('in-sector');
                        }
                    }
                }

                // Click en el rectangulo: mostramos el popup de acciones.
                sectorRect.addEventListener('click', function (ev) {
                    ev.stopPropagation();
                    const rectBox = sectorRect.getBoundingClientRect();
                    const containerBox = container.getBoundingClientRect();
                    const x = rectBox.left - containerBox.left + rectBox.width / 2;
                    const y = rectBox.top - containerBox.top + 6;
                    showActionPopup(x, y, sectorRect.dataset.sectorId);
                });

                svg.appendChild(sectorRect);
                svg.appendChild(sectorLabel);
            });
        }

        function updateSummary() {
            // Refresca los textos de resumen (inicio, fin y tamano seleccionado).
            if (startEl) {
                startEl.textContent = state.start
                    ? 'F' + state.start.fila + ' C' + state.start.columna
                    : '-';
            }

            if (endEl) {
                endEl.textContent = state.end
                    ? 'F' + state.end.fila + ' C' + state.end.columna
                    : '-';
            }

            if (sizeEl) {
                // Sin dos puntos no existe rectangulo y no mostramos area.
                if (!state.start || !state.end) {
                    sizeEl.textContent = '-';
                    return;
                }

                // Ordena extremos para soportar clic en cualquier direccion.
                const filaInicio = Math.min(state.start.fila, state.end.fila);
                const filaFin = Math.max(state.start.fila, state.end.fila);
                const colInicio = Math.min(state.start.columna, state.end.columna);
                const colFin = Math.max(state.start.columna, state.end.columna);

                // Calcula filas, columnas y total de asientos del rectangulo.
                const totalFilas = filaFin - filaInicio + 1;
                const totalColumnas = colFin - colInicio + 1;
                const totalAsientos = totalFilas * totalColumnas;

                sizeEl.textContent = totalFilas + 'x' + totalColumnas + ' (' + totalAsientos + ' asientos)';
            }
        }

        function isInsidePreview(fila, columna) {
            // Si falta inicio o fin, no hay previsualizacion activa.
            if (!state.start || !state.end) {
                return false;
            }

            // Comprueba si un asiento cae dentro del rectangulo seleccionado.
            const filaInicio = Math.min(state.start.fila, state.end.fila);
            const filaFin = Math.max(state.start.fila, state.end.fila);
            const colInicio = Math.min(state.start.columna, state.end.columna);
            const colFin = Math.max(state.start.columna, state.end.columna);

            return fila >= filaInicio && fila <= filaFin && columna >= colInicio && columna <= colFin;
        }

        function refreshSeatClasses() {
            // Recorre todos los asientos y aplica clases CSS segun su estado.
            state.seats.forEach((seatNode, key) => {
                const [filaStr, columnaStr] = key.split('-');
                const fila = Number(filaStr);
                const columna = Number(columnaStr);

                // Limpia clases anteriores para evitar estados inconsistentes.
                seatNode.classList.remove('is-start', 'is-end', 'is-preview');

                // Marca asiento inicial.
                if (state.start && state.start.fila === fila && state.start.columna === columna) {
                    seatNode.classList.add('is-start');
                }

                // Marca asiento final.
                if (state.end && state.end.fila === fila && state.end.columna === columna) {
                    seatNode.classList.add('is-end');
                }

                // Resalta asientos dentro del area de preview.
                if (isInsidePreview(fila, columna)) {
                    seatNode.classList.add('is-preview');
                }
            });
        }

        function clearSelection() {
            // Resetea seleccion y actualiza la vista y el resumen.
            state.start = null;
            state.end = null;
            refreshSeatClasses();
            updateSummary();
        }

        function onSeatClick(fila, columna) {
            // Primer clic: inicio. Segundo clic: fin.
            // Si ya habia rectangulo completo, empezamos una nueva seleccion.
            if (!state.start || state.end) {
                state.start = { fila, columna };
                state.end = null;
            } else {
                state.end = { fila, columna };
            }

            // Sincroniza estado visual y panel de resumen.
            refreshSeatClasses();
            updateSummary();
        }

        function drawGrid() {
            // Limpia SVG por si se vuelve a dibujar.
            // Esto evita duplicar nodos cuando se vuelva a llamar a drawGrid().
            svg.innerHTML = '';

            // Fondo del lienzo.
            const bg = createSvgNode('rect', {
                x: 0,
                y: 0,
                width: viewWidth,
                height: viewHeight,
                rx: 14,
                class: 'sector-map-bg',
            });
            svg.appendChild(bg);

            // Barra de escenario para orientar al usuario.
            const stage = createSvgNode('rect', {
                x: padLeft,
                y: 8,
                width: gridWidth,
                height: 20,
                rx: 10,
                class: 'sector-map-stage',
            });
            svg.appendChild(stage);

            // Texto del escenario.
            const stageLabel = createSvgNode('text', {
                x: padLeft + gridWidth / 2,
                y: 23,
                class: 'sector-map-stage-label',
                'text-anchor': 'middle',
            });
            stageLabel.textContent = 'ESCENARIO';
            svg.appendChild(stageLabel);

            // Nota: los sectores se pintarán despues de crear los asientos
            // para que queden en primer plano y capturen clicks.

            // Dibuja filas: etiqueta lateral numerica + linea horizontal.
            for (let row = 1; row <= rows; row++) {
                const y = padTop + (row - 1) * yStep;

                const rowLabel = createSvgNode('text', {
                    x: 34,
                    y: y + 4,
                    class: 'sector-map-axis-label',
                    'text-anchor': 'middle',
                });
                rowLabel.textContent = String(row);
                svg.appendChild(rowLabel);

                const hLine = createSvgNode('line', {
                    x1: padLeft,
                    y1: y,
                    x2: padLeft + gridWidth,
                    y2: y,
                    class: 'sector-map-grid-line',
                });
                svg.appendChild(hLine);
            }

            // Dibuja columnas: etiqueta inferior + linea vertical.
            for (let col = 1; col <= cols; col++) {
                const x = padLeft + (col - 1) * xStep;

                const colLabel = createSvgNode('text', {
                    x,
                    y: viewHeight - 6,
                    class: 'sector-map-axis-label',
                    'text-anchor': 'middle',
                });
                colLabel.textContent = String(col);
                svg.appendChild(colLabel);

                const vLine = createSvgNode('line', {
                    x1: x,
                    y1: padTop,
                    x2: x,
                    y2: padTop + gridHeight,
                    class: 'sector-map-grid-line',
                });
                svg.appendChild(vLine);
            }

            // Crea cada asiento como grupo SVG clicable.
            for (let row = 1; row <= rows; row++) {
                for (let col = 1; col <= cols; col++) {
                    const x = padLeft + (col - 1) * xStep;
                    const y = padTop + (row - 1) * yStep;

                    const seatGroup = createSvgNode('g', {
                        class: 'seat-node',
                        'data-row': row,
                        'data-col': col,
                        'aria-label': 'Fila ' + row + ', columna ' + col,
                    });

                    const seatCircle = createSvgNode('circle', {
                        cx: x,
                        cy: y,
                        r: seatRadius,
                    });

                    // Al hacer clic, actualiza la seleccion en el estado.
                    // El asiento no decide por si solo: delega en onSeatClick().
                    seatGroup.appendChild(seatCircle);
                    seatGroup.addEventListener('click', function () {
                        onSeatClick(row, col);
                    });

                    // Guarda referencia para poder reestilar rapido despues.
                    svg.appendChild(seatGroup);
                    state.seats.set(seatKey(row, col), seatGroup);
                }
            }

            // Pintamos ahora los sectores en primer plano (encima de asientos)
            drawSectorBackgrounds();

            // Muestra resumen inicial vacio.
            updateSummary();
        }

        // Manejo del popup de acciones de sector
        const actionPopup = container.querySelector('#sector-action-popup');

        function showActionPopup(x, y, sectorId) {
            if (!actionPopup) return;
            actionPopup.removeAttribute('hidden');
            actionPopup.style.left = x + 'px';
            actionPopup.style.top = y + 'px';
            actionPopup.dataset.sectorId = sectorId || '';
        }

        function hideActionPopup() {
            if (!actionPopup) return;
            actionPopup.setAttribute('hidden', '');
            delete actionPopup.dataset.sectorId;
        }

        function deleteSector(sectorId) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (!csrfToken) {
                alert('Error: CSRF token no encontrado');
                return;
            }

            fetch('/admin/sectores/' + sectorId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json',
                },
            })
            .then(response => {
                if (response.ok) {
                    // Recargar la página para actualizar el mapa
                    window.location.reload();
                } else {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Error al borrar el sector');
                    });
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        // Cierra popup al clicar fuera
        document.addEventListener('click', function () {
            hideActionPopup();
        });

        // Botones del popup: por ahora solo loguean la accion
        if (actionPopup) {
            const delBtn = actionPopup.querySelector('[data-sector-delete]');
            const editBtn = actionPopup.querySelector('[data-sector-edit]');
            if (delBtn) delBtn.addEventListener('click', function (ev) {
                ev.stopPropagation();
                const id = actionPopup.dataset.sectorId;
                if (confirm('¿Estás seguro de que quieres borrar este sector?')) {
                    deleteSector(id);
                }
                hideActionPopup();
            });
            if (editBtn) editBtn.addEventListener('click', function (ev) {
                ev.stopPropagation();
                const id = actionPopup.dataset.sectorId;
                console.log('Editar sector (pendiente):', id);
                hideActionPopup();
            });
        }

        // GUARDAR SECTOR: envía la selección al backend y actualiza la vista.
        const saveButton = document.querySelector('[data-save-sector]');
        const eventoId = container.dataset.eventoId || null;

        async function saveSector() {
            if (!state.start || !state.end) {
                alert('Selecciona asiento inicial y final antes de guardar.');
                return;
            }

            const filaInicio = Math.min(state.start.fila, state.end.fila);
            const filaFin = Math.max(state.start.fila, state.end.fila);
            const colInicio = Math.min(state.start.columna, state.end.columna);
            const colFin = Math.max(state.start.columna, state.end.columna);

            const totalFilas = filaFin - filaInicio + 1;
            const totalColumnas = colFin - colInicio + 1;

            const nombre = window.prompt('Nombre del sector', 'Nuevo sector');
            if (nombre === null) return; // usuario canceló
            const desc = window.prompt('Descripción del sector', 'Descripción del sector');
            const color_hex = window.prompt('Color (hex)', '#5ba8ff') || '#5ba8ff';

            const payload = {
                nombre: nombre,
                descripcion: desc,
                color_hex: color_hex,
                inicio: { fila: filaInicio, columna: colInicio },
                fin: { fila: filaFin, columna: colFin },
            };

            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrf = tokenMeta ? tokenMeta.getAttribute('content') : null;

            try {
                const res = await fetch('/admin/sectores', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf || '',
                    },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin',
                });

                if (!res.ok) {
                    let errText = 'Error guardando sector.';
                    try {
                        const j = await res.json();
                        errText = j.message || (j.errors ? JSON.stringify(j.errors) : errText);
                    } catch (e) {}
                    alert(errText);
                    return;
                }

                // Intentamos parsear la respuesta JSON con el sector creado.
                let created = null;
                try {
                    created = await res.json();
                } catch (e) {
                    // Si no viene JSON, asumimos que fue correcto.
                }

                // Normalizar la respuesta para obtener un objeto sector con los campos esperados.
                const maybe = created && (created.data || created) ? (created.data || created) : null;
                function normalizeServerSector(s) {
                    if (!s) return null;
                    const a = s.attributes || s; // por si viene en formato JSON:API
                    return {
                        id: a.id || s.id || Date.now(),
                        nombre: a.nombre || a.name || s.nombre || s.name || nombre,
                        color_hex: a.color_hex || a.colorHex || s.color_hex || s.colorHex || color_hex,
                        fila_inicio: a.fila_inicio ?? a.filaInicio ?? s.fila_inicio ?? s.filaInicio ?? filaInicio,
                        fila_fin: a.fila_fin ?? a.filaFin ?? s.fila_fin ?? s.filaFin ?? filaFin,
                        columna_inicio: a.columna_inicio ?? a.columnaInicio ?? s.columna_inicio ?? s.columnaInicio ?? colInicio,
                        columna_fin: a.columna_fin ?? a.columnaFin ?? s.columna_fin ?? s.columnaFin ?? colFin,
                    };
                }

                const normalized = normalizeServerSector(maybe);

                if (normalized) {
                    console.log('Sector creado (normalized):', normalized);
                } else {
                    console.log('Sector creado (provisional): no normalized data from server, will reload desde API');
                }

                if (eventoId) {
                    try {
                        const listRes = await fetch('/api/sectores', {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' },
                            credentials: 'same-origin',
                        });

                        if (listRes.ok) {
                            const listJson = await listRes.json();
                            const data = Array.isArray(listJson.data) ? listJson.data : [];
                            sectors.length = 0;
                            data.forEach(s => sectors.push(s));
                        } else {
                            console.warn('No se pudo recargar sectores desde la API, usando dato local.');
                            if (normalized) sectors.push(normalized);
                        }
                    } catch (e) {
                        console.error('Error recargando sectores:', e);
                        if (normalized) sectors.push(normalized);
                    }
                } else {
                    if (normalized) sectors.push(normalized);
                }

                // Redibujamos el mapa para reflejar el nuevo sector.
                drawGrid();
                alert('Sector guardado correctamente.');
                clearSelection();
            } catch (err) {
                console.error(err);
                alert('Error de conexión al guardar el sector.');
            }
        }

        if (saveButton) {
            saveButton.addEventListener('click', function (ev) {
                ev.preventDefault();
                saveSector();
            });
        }

        // Conecta boton de limpiar (si existe en la vista).
        if (clearButton) {
            clearButton.addEventListener('click', clearSelection);
        }

        // Render inicial del mapa.
        drawGrid();
    }

    // Espera al DOM si la pagina aun no esta lista.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSectorMapEditor);
        return;
    }

    // Si el DOM ya esta listo, inicializa directamente.
    initSectorMapEditor();
})();
