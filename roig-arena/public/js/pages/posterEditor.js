// Editor de posters del evento (modal + guardado via AJAX).
//
// Flujo general:
// 1) Pulsar lapiz -> abre modal y precarga las URLs actuales.
// 2) Editar campos y guardar -> PATCH al endpoint de actualizacion del evento.
// 3) Si todo va bien, actualiza datos en memoria y refresca la vista del poster ancho.
document.addEventListener('DOMContentLoaded', () => {
    // Contenedor del hero con data-attributes necesarios para editar posters.
    const editor = document.querySelector('[data-poster-editor]');
    // Modal reutilizado para editar poster normal y poster ancho.
    const modal = document.querySelector('[data-poster-modal]');

    // Si no existe el editor o el modal, no hay nada que inicializar.
    if (!editor || !modal) return;

    // Elementos base del modal y configuracion de red.
    const backdrop = modal.querySelector('[data-modal-backdrop]');
    const closeButtons = modal.querySelectorAll('[data-modal-close]');
    const form = modal.querySelector('[data-poster-form]');
    const updateUrl = editor.dataset.updateUrl || '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // Inputs editables de cada campo de imagen.
    const inputs = {
        poster_url: modal.querySelector('[data-poster-input="poster_url"]'),
        poster_ancho_url: modal.querySelector('[data-poster-input="poster_ancho_url"]'),
    };

    // Preview visual que se actualiza en pantalla tras guardar.
    const preview = {
        poster_ancho_url: editor.querySelector('[data-poster-preview="poster_ancho_url"]'),
    };

    // Abre modal y carga SIEMPRE el valor actual guardado en dataset.
    // Esto evita mostrar valores obsoletos si ya se edito antes.
    function openModal(targetField = 'poster_ancho_url') {
        const currentPosterUrl = editor.dataset.currentPosterUrl ?? '';
        const currentPosterAnchoUrl = editor.dataset.currentPosterAnchoUrl ?? '';

        // Si un campo estaba vacio, aqui sigue vacio (no se rellena con historicos).
        if (inputs.poster_url) inputs.poster_url.value = currentPosterUrl;
        if (inputs.poster_ancho_url) inputs.poster_ancho_url.value = currentPosterAnchoUrl;

        modal.hidden = false;

        // Coloca foco en el campo accionado por el lapiz y selecciona el texto.
        const input = inputs[targetField] || inputs.poster_ancho_url || inputs.poster_url;
        if (input) {
        input.focus();
        input.select();
        }
    }

    // Cierra modal sin modificar datos.
    function closeModal() {
        modal.hidden = true;
    }

    // Refresca la imagen del hero cuando cambia poster_ancho_url.
    // Si se vacia el campo, reemplaza la imagen por el estado "Sin imagen disponible".
    function applyPreview(fieldName, value) {
        if (fieldName === 'poster_ancho_url') {
            const existingPreview = preview.poster_ancho_url;

            if (!existingPreview) return;

            if (value) {
                if (existingPreview.tagName === 'IMG') {
                    existingPreview.src = value;
                } else {
                    const img = document.createElement('img');
                    img.src = value;
                    img.alt = editor.closest('.event-hero')?.querySelector('img')?.alt || '';
                    img.className = 'event-hero-image';
                    img.dataset.posterPreview = 'poster_ancho_url';
                    existingPreview.replaceWith(img);
                    preview.poster_ancho_url = img;
                }
            } else {
                const empty = document.createElement('div');
                empty.className = 'event-hero-image event-no-image';
                empty.textContent = 'Sin imagen disponible';
                empty.dataset.posterPreview = 'poster_ancho_url';
                existingPreview.replaceWith(empty);
                preview.poster_ancho_url = empty;
            }
        }
    }

    editor.querySelectorAll('[data-poster-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            // El boton indica que input priorizar al abrir el modal.
            openModal(button.dataset.posterToggle || 'poster_ancho_url');
        });
    });

    // Cierres de modal: click en backdrop, botones de cerrar y tecla Escape.
    backdrop?.addEventListener('click', closeModal);
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        // Normaliza a string vacio si el usuario deja campos en blanco.
        const posterUrl = inputs.poster_url?.value.trim() || '';
        const posterAnchoUrl = inputs.poster_ancho_url?.value.trim() || '';

        try {
            // Se envia como POST con _method=PATCH (patron Laravel).
            const formData = new FormData(form);
            formData.set('poster_url', posterUrl);
            formData.set('poster_ancho_url', posterAnchoUrl);
            formData.set('_method', 'PATCH');

            const response = await fetch(updateUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload?.message || payload?.error || 'No se pudo actualizar el póster.';
                alert(message);
                return;
            }

            // Persistimos en dataset los ultimos valores para futuras aperturas.
            editor.dataset.currentPosterUrl = posterUrl;
            editor.dataset.currentPosterAnchoUrl = posterAnchoUrl;

            // Actualizamos la vista del hero en caliente, sin recargar pagina.
            applyPreview('poster_ancho_url', posterAnchoUrl);
            closeModal();
        } catch (error) {
            alert('Error de red al actualizar el póster.');
        }
    });
});
