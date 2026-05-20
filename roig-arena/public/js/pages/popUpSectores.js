/**
 * Popup para añadir sectores a eventos
 */

document.addEventListener('DOMContentLoaded', function () {
    // El botón abre el modal de gestión de sectores del evento.
    const addBtns = document.querySelectorAll('[data-add-sector-button]');
    const modal = document.querySelector('#sector-modal');

    if (!addBtns.length || !modal) return;

    const backdrop = modal.querySelector('[data-modal-backdrop]');
    const closeButtons = modal.querySelectorAll('[data-modal-close]');
    const listEl = modal.querySelector('#sector-list');
    const searchInput = modal.querySelector('#sector-search');
    const searchBtn = modal.querySelector('#sector-search-btn');
    const attachUrl = modal.getAttribute('data-attach-url') || '';
    const detachUrlTemplate = modal.getAttribute('data-detach-url-template') || '';
    // Lista de ids de sectores ya vinculados al evento.
    const existing = JSON.parse(modal.getAttribute('data-existing-sectores') || '[]').map(Number);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    // Abre el modal y carga la lista inicial de sectores.
    function openModal() {
        modal.hidden = false;
        if (searchInput) searchInput.focus();
        fetchSectores();
    }

    // Cierra el modal y limpia el listado/buscador para la siguiente apertura.
    function closeModal() {
        modal.hidden = true;
        if (listEl) listEl.innerHTML = '';
        if (searchInput) searchInput.value = '';
    }

    // Recupera sectores desde la API pública.
    // Si hay texto de búsqueda, consulta el endpoint filtrado.
    function fetchSectores(q = '') {
        if (!listEl) return;

        listEl.innerHTML = '<p class="muted">Cargando sectores...</p>';

        const url = q
            ? '/api/sectores/buscar?q=' + encodeURIComponent(q)
            : '/api/sectores';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const sectores = Array.isArray(data.data) ? data.data : [];
                if (sectores.length === 0) {
                    listEl.innerHTML = '<p class="muted">No hay sectores disponibles.</p>';
                    return;
                }
                renderList(sectores);
            })
            .catch(error => {
                console.error(error);
                listEl.innerHTML = '<p class="muted">Error cargando sectores.</p>';
            });
    }

    // Comprueba si un sector ya está asociado al evento.
    function isAssociated(id) {
        return existing.includes(Number(id));
    }

    // Construye la URL de borrado del sector dentro del evento.
    // Esta URL sirve para quitar la relación evento-sector, no para borrar el sector globalmente.
    function getDetachUrl(id) {
        return detachUrlTemplate ? detachUrlTemplate.replace('__ID__', id) : '';
    }

    // Renderiza cada resultado de búsqueda como una fila con acciones.
    function renderList(sectores) {
        listEl.innerHTML = '';

        sectores.forEach(item => {
            const sector = item.data ? item.data : item;
            const id = Number(sector.id);
            const associated = isAssociated(id);

            const card = document.createElement('div');
            card.className = 'sector-row';
            card.dataset.sectorId = String(id);

            card.innerHTML = `
                <div class="sector-info">
                    ${sector.imagen_url ? `<img src="${sector.imagen_url}" alt="${escapeHtml(sector.nombre)}" class="sector-thumb">` : ''}
                    <div>
                        <div class="sector-name">${escapeHtml(sector.nombre)}</div>
                        ${sector.descripcion ? `<div class="sector-desc muted">${escapeHtml(sector.descripcion)}</div>` : ''}
                    </div>
                </div>
                <div class="sector-actions">
                    <button type="button" class="btn btn-sm btn-primary add-sector-btn" data-sector-id="${id}" ${associated ? 'disabled' : ''}>
                        ${associated ? 'Añadido' : 'Añadir'}
                    </button>
                </div>
            `;

            listEl.appendChild(card);
        });

        listEl.querySelectorAll('.add-sector-btn').forEach(btn => {
            btn.addEventListener('click', onAddSector);
        });

        listEl.querySelectorAll('.delete-sector-btn').forEach(btn => {
            btn.addEventListener('click', onDeleteSector);
        });
    }

    // Marca una fila como ya añadida para evitar duplicados visuales.
    function markRowAsAssociated(row) {
        const addBtn = row.querySelector('.add-sector-btn');
        if (addBtn) {
            addBtn.disabled = true;
            addBtn.textContent = 'Añadido';
        }
    }

    // Añade un sector al evento y actualiza la tabla de precios sin recargar.
    function onAddSector(e) {
        e.preventDefault();

        const btn = e.currentTarget;
        const id = Number(btn.getAttribute('data-sector-id'));
        if (!id || !attachUrl) return;

        btn.disabled = true;
        btn.textContent = 'Añadiendo...';

        const precioInput = prompt('Introduce el precio para este sector (en euros):', '0.00');
        if (precioInput === null) {
            btn.disabled = false;
            btn.textContent = 'Añadir';
            return;
        }

        const precioValue = parseFloat(precioInput.replace(',', '.'));
        if (isNaN(precioValue) || precioValue <= 0) {
            alert('Por favor, introduce un precio válido (número positivo).');
            btn.disabled = false;
            btn.textContent = 'Añadir';
            return;
        }

        fetch(attachUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ sector_id: id, precio: precioValue }),
        })
            .then(response => response.json().then(body => ({ status: response.status, body })))
            .then(({ status, body }) => {
                if (status < 200 || status >= 300) {
                    throw new Error(body.message || body.error || 'Error al añadir sector');
                }

                if (!existing.includes(id)) {
                    existing.push(id);
                }

                const precio = body.data || null;
                const row = btn.closest('.sector-row');
                if (row) {
                    markRowAsAssociated(row);
                    appendSectorRowToTable(precio, row);
                }

                // Reengancha los controles sobre la fila recién insertada.
                window.initPriceInlineEditors?.();
                window.initMultiDeleteUI?.();
            })
            .catch(error => {
                console.error(error);
                btn.disabled = false;
                btn.textContent = 'Añadir';
                alert(error.message || 'Error de red al añadir sector');
            });
    }

    // Localiza el tbody de la tabla de precios donde se muestran los sectores del evento.
    function getPricingTableBody() {
        return document.querySelector('.pricing-table tbody');
    }

    // Formatea el precio para mostrarlo con el mismo estilo que el resto de la vista.
    function formatPrice(value) {
        const numericValue = Number.parseFloat(value);
        if (Number.isNaN(numericValue)) return '0,00€';

        return new Intl.NumberFormat('es-ES', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(numericValue) + '€';
    }

    // Elimina de la tabla la fila que corresponda al sector borrado.
    function removeSectorRowFromTable(sectorId) {
        const tableBody = getPricingTableBody();
        if (!tableBody) return;

        const rows = Array.from(tableBody.querySelectorAll('tr'));
        rows.forEach(row => {
            const rowSectorId = Number(row.dataset.sectorId || row.querySelector('[data-sector-price-checkbox]')?.dataset.sectorId);
            if (rowSectorId && rowSectorId === Number(sectorId)) {
                row.remove();
            }
        });
    }

    // Inserta en la tabla de precios la fila del sector recién añadido.
    // La fila incluye borrado, edición de precio y checkbox de selección.
    function appendSectorRowToTable(precio, sourceRow) {
        const tableBody = getPricingTableBody();
        if (!tableBody || !precio || !precio.sector) return;

        if (tableBody.querySelector(`tr[data-sector-id="${precio.sector.id}"]`)) {
            return;
        }

        const row = document.createElement('tr');
        // Guardamos ambos ids para que otras capas de UI puedan identificar la fila.
        row.dataset.sectorId = String(precio.sector.id);
        row.dataset.precioId = String(precio.id);

        const descriptionHtml = precio.sector.descripcion
            ? `<br><span class="muted">${escapeHtml(precio.sector.descripcion)}</span>`
            : '';

        row.innerHTML = `
            <td>
                <strong>${escapeHtml(precio.sector.nombre)}</strong>
                ${descriptionHtml}
            </td>
            <td class="price-highlight">
                <span id="sector-price-display-${precio.id}" data-sector-price-display>${formatPrice(precio.precio)}</span>
            </td>
            <td>
                ${precio.disponible ? '<span class="badge badge-success">Disponible</span>' : '<span class="badge badge-danger">Agotado</span>'}
            </td>
            <td class="pricing-table-actions">
                <!-- Borrado/desactivación del sector para este evento -->
                <form action="/admin/precios/${precio.id}" method="POST" style="display: inline;" data-row-delete-form>
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="event-card-trash" data-row-delete-button aria-label="Eliminar sector" onclick="return confirm('¿Estás seguro de que quieres desactivar este sector?')">
                        🗑️
                    </button>
                </form>
                <button type="button" class="event-title-edit-button" data-row-edit-button data-sector_price-toggle aria-label="Editar precio del sector">
                    ✎
                </button>
                <!-- Editor inline del precio del sector -->
                <form
                    class="event-sector_price-form"
                    data-sector-price-editor
                    data-sector-price-display="#sector-price-display-${precio.id}"
                    action="/admin/precios/${precio.id}"
                    method="POST"
                    hidden
                >
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <input
                        type="number"
                        name="precio"
                        value="${Number.parseFloat(precio.precio || 0).toFixed(2)}"
                        min="0"
                        step="0.01"
                        required
                        class="event-sector_price-input"
                        data-sector-price-input
                        aria-label="Precio del sector ${escapeHtml(precio.sector.nombre)}"
                    >
                </form>

                <!-- Checkbox usado por el borrado masivo de precios -->
                <label for="sector-price-select-${precio.id}" style="display: inline-flex; align-items: center; gap: 0.35rem; margin-right: 0.75rem;">
                    <input
                        type="checkbox"
                        id="sector-price-select-${precio.id}"
                        name="precios_seleccionados[]"
                        value="${precio.id}"
                        data-sector-price-checkbox
                        data-sector-id="${precio.sector.id}"
                        data-precio-id="${precio.id}"
                    >
                </label>
            </td>
        `;

        const addRow = tableBody.querySelector('.pricing-table-add-row');
        if (addRow) {
            tableBody.insertBefore(row, addRow);
        } else {
            tableBody.appendChild(row);
        }
    }

    // Escapa texto dinámico para evitar inyectar HTML accidentalmente.
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Conecta los eventos del modal.
    addBtns.forEach(button => button.addEventListener('click', openModal));
    backdrop?.addEventListener('click', closeModal);
    closeButtons.forEach(button => button.addEventListener('click', closeModal));

    // Búsqueda con debounce para no disparar una petición por cada tecla.
    if (searchInput) {
        let timeoutId;
        searchInput.addEventListener('input', function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                fetchSectores(searchInput.value.trim());
            }, 300);
        });
    }

    // Botón explícito de búsqueda por si el usuario no quiere esperar al debounce.
    if (searchBtn) {
        searchBtn.addEventListener('click', function (e) {
            e.preventDefault();
            fetchSectores(searchInput ? searchInput.value.trim() : '');
        });
    }

    // Cierre rápido con Escape para mejorar la experiencia de uso.
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
});
