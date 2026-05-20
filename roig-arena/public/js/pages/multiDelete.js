// Habilita o deshabilita los controles de acciones (editar/borrar) de una fila concreta.
// - checkbox: checkbox de la fila actual.
// - isEnabled: true para habilitar acciones, false para deshabilitarlas.
function setRowActionsState(checkbox, isEnabled) {
	// Localiza la fila completa donde vive el checkbox.
	const row = checkbox.closest('tr');
	if (!row) return;

	// Busca los elementos de acción individuales de esa fila.
	const editButton = row.querySelector('[data-row-edit-button]');
	const deleteForm = row.querySelector('[data-row-delete-form]');
	const deleteButton = row.querySelector('[data-row-delete-button]');

	// Alterna estado habilitado del botón de editar.
	if (editButton) {
		editButton.disabled = !isEnabled;
		editButton.setAttribute('aria-disabled', String(!isEnabled));
	}

	// Alterna estado habilitado del botón de borrado.
	// No se oculta nada, solo se desactiva la interacción.
	if (deleteButton) {
		deleteButton.disabled = !isEnabled;
		deleteButton.setAttribute('aria-disabled', String(!isEnabled));
	}

	// Marca visual opcional en la fila para poder estilizar en CSS si quieres.
	if (deleteForm) {
		deleteForm.classList.toggle('is-disabled', !isEnabled);
	}
	row.classList.toggle('row-actions-disabled', !isEnabled);
}

// --- Gestión de selección de precios (para el borrar seleccionados) ---
// Conservar en memoria los ids de `precio` seleccionados por checkbox.
// Usamos un Set para evitar duplicados y acceder/actualizar eficientemente.
const selectedPrecioIds = new Set();

/**
 * Devuelve un array con los ids de precio actualmente seleccionados.
 * Uso: `window.multiDelete.getSelectedPrecioIds()`
 */
function getSelectedPrecioIds() {
	return Array.from(selectedPrecioIds).map((id) => Number(id));
}

// Exponer API mínima global para que otras partes puedan leer la selección.
window.multiDelete = window.multiDelete || {};
window.multiDelete.getSelectedPrecioIds = getSelectedPrecioIds;


// Sincroniza la zona de acciones globales (cabecera "Acciones").
// Solo se muestra cuando TODOS los checkboxes están marcados.
function syncBulkActions(checkboxes) {
	const bulkActionsControls = document.getElementById('bulk-actions-controls');
	if (!bulkActionsControls || checkboxes.length === 0) return;

	// Comprueba selección total de filas.
	const allChecked = checkboxes.some((checkbox) => checkbox.checked);
	// Muestra acciones masivas si la selección es completa.
	bulkActionsControls.style.display = allChecked ? 'inline-flex' : 'none';
}

// Inicializa toda la lógica de checkboxes y sincronización de UI.
function initMultiDeleteUI() {
	// Obtiene todas las checkboxes de sectores de la tabla.
	const checkboxes = Array.from(document.querySelectorAll('[data-sector-price-checkbox]'));
	if (checkboxes.length === 0) return;

	// Estado visual por fila: marcada => oculta acciones; no marcada => muestra acciones.
	const applyState = (checkbox) => {
		setRowActionsState(checkbox, !checkbox.checked);
	};

	// 1) Aplica estado inicial al cargar.
	// 2) Escucha cambios para actualizar fila + acciones globales.
	checkboxes.forEach((checkbox) => {
		if (checkbox.dataset.multiDeleteInitialized === 'true') {
			return;
		}

		// Estado inicial: si viene marcada, la añadimos al Set de selección.
		const precioId = checkbox.dataset.precioId;
		if (checkbox.checked && precioId) {
			selectedPrecioIds.add(precioId);
		}
		applyState(checkbox);

		checkbox.addEventListener('change', () => {
			// Mantener el Set de ids actualizado.
			if (precioId) {
				if (checkbox.checked) selectedPrecioIds.add(precioId);
				else selectedPrecioIds.delete(precioId);
			}

			// Al cambiar una checkbox, se recalcula su fila y el estado global.
			applyState(checkbox);
			syncBulkActions(checkboxes);

			// Emitir evento DOM para listeners externos interesados en cambios de selección.
			document.dispatchEvent(new CustomEvent('multiDelete.selectionChanged', { detail: getSelectedPrecioIds() }));
		});

		checkbox.dataset.multiDeleteInitialized = 'true';
	});

	// Sincroniza estado global al inicio (por si la vista carga con checks marcados).
	syncBulkActions(checkboxes);

	// Conectar botón de borrado masivo (cabecera).
	const bulkDeleteBtn = document.querySelector('[data-bulk-delete]');
	if (bulkDeleteBtn && bulkDeleteBtn.dataset.bulkDeleteInitialized !== 'true') {
		bulkDeleteBtn.addEventListener('click', async (e) => {
			e.preventDefault();
			const ids = getSelectedPrecioIds();
			if (!ids || ids.length === 0) {
				alert('No hay precios seleccionados.');
				return;
			}

			if (!confirm('¿Deseas eliminar los ' + ids.length + ' precios seleccionados? Esta acción no se puede deshacer.')) return;

			const url = bulkDeleteBtn.dataset.bulkDeleteUrl;
			if (!url) {
				console.error('URL de borrado masivo no encontrada en el botón.');
				return;
			}

			const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

			try {
				const res = await fetch(url, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'Accept': 'application/json',
						'X-CSRF-TOKEN': csrfToken,
					},
					body: JSON.stringify({ ids }),
				});

				if (!res.ok) {
					const text = await res.text();
					throw new Error(text || 'Error en la petición');
				}

				// Eliminar visualmente las filas afectadas y limpiar selección.
				ids.forEach((id) => {
					const checkbox = document.querySelector('[data-precio-id="' + id + '"]');
					if (checkbox) {
						const row = checkbox.closest('tr');
						if (row) row.remove();
					}
					selectedPrecioIds.delete(String(id));
				});

				// Emitir evento y recalcular UI.
				document.dispatchEvent(new CustomEvent('multiDelete.selectionChanged', { detail: getSelectedPrecioIds() }));
				syncBulkActions(checkboxes);
				alert('Precios eliminados correctamente.');
			} catch (err) {
				console.error(err);
				alert('Error al eliminar precios seleccionados. Comprueba la consola para más detalles.');
			}
		});

		bulkDeleteBtn.dataset.bulkDeleteInitialized = 'true';
	}
}

// Espera a que el DOM esté listo antes de buscar elementos y enlazar eventos.
document.addEventListener('DOMContentLoaded', initMultiDeleteUI);
window.initMultiDeleteUI = initMultiDeleteUI;
