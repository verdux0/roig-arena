// Función generalizada para edición inline
function initInlineEditor(options) {
    const {
        containerSelector,
        displaySelector,
        toggleSelector,
        formSelector,
        inputSelector,
        updateUrl: updateUrlOption,
        fieldName = 'value' // Nombre del campo en el form (por defecto 'value')
    } = options;

    const container = document.querySelector(containerSelector);
    if (!container) return;
    const updateUrl = updateUrlOption || container.dataset.updateUrl || container.querySelector(formSelector)?.getAttribute('action');
    const toggleButton = container.querySelector(toggleSelector);
    const titleDisplay = container.querySelector(displaySelector);
    const form = container.querySelector(formSelector);
    const input = container.querySelector(inputSelector);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    if (!toggleButton || !titleDisplay || !form || !input || !updateUrl || !csrfToken) return;

    const isDateField = fieldName === 'fecha';

    const normalizeDateValue = (value) => {
        if (!value) return '';
        if (/^\d{4}-\d{2}-\d{2}/.test(value)) {
            return value.slice(0, 10);
        }
        const match = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
        if (match) {
            return `${match[3]}-${match[2]}-${match[1]}`;
        }
        return value;
    };

    const formatDateForDisplay = (value) => {
        const normalizedValue = normalizeDateValue(value);
        const match = normalizedValue.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return value;
        return `${match[3]}/${match[2]}/${match[1]}`;
    };

    const getEditableValue = () => {
        const displayValue = titleDisplay.textContent.trim();
        if (!isDateField) {
            return displayValue;
        }

        const normalizedValue = normalizeDateValue(displayValue);
        return /^\d{4}-\d{2}-\d{2}$/.test(normalizedValue) ? normalizedValue : '';
    };

    const openEditor = () => {
        titleDisplay.hidden = true;
        toggleButton.hidden = true;
        form.hidden = false;
        input.hidden = false;
        if (isDateField) {
            input.value = input.value || getEditableValue();
        } else {
            input.value = input.value || getEditableValue();
        }
        input.focus();
        input.select();
    };
    const closeEditor = () => {
        form.hidden = true;
        input.hidden = true;
        toggleButton.hidden = false;
        titleDisplay.hidden = false;
        input.setCustomValidity('');
    };

    toggleButton.addEventListener('click', openEditor);
    input.addEventListener('input', () => {
        input.setCustomValidity('');
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            input.value = getEditableValue();
            closeEditor();
        }
        if (event.key === 'Enter') {
            event.preventDefault();
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.click();
                return;
            }

            form.submit();
        }
    });
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const newValue = input.value.trim();
        const normalizedValue = isDateField ? normalizeDateValue(newValue) : newValue;

        if (!normalizedValue) {
            input.setCustomValidity('El valor no puede estar vacío.');
            input.reportValidity();
            return;
        }

        try {
            const formData = new FormData(form);
            // Asegurarse de que el campo tenga el nombre correcto
            formData.set(fieldName, normalizedValue);
            formData.set('_method', 'PATCH');

            // Aquí enviaríamos la solicitud al backend para actualizar el valor
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
                const message = payload?.message || payload?.error || 'No se pudo actualizar.';
                input.setCustomValidity(message);
                input.reportValidity();
                input.focus();
                return;
            }

                const updatedValue = payload?.data?.[fieldName] ?? normalizedValue;
                const displayValue = isDateField ? formatDateForDisplay(updatedValue) : updatedValue;
                titleDisplay.textContent = displayValue;
                input.value = normalizedValue;
                closeEditor();
            } catch (error) {
                input.setCustomValidity('Error de red al actualizar.');
                input.reportValidity();
            }
    });
}
// Inicializar editores inline
document.addEventListener('DOMContentLoaded', () => {
    // Editor para el título del evento
    initInlineEditor({
        containerSelector: '[data-event-title-editor]',
        displaySelector: '[data-event-title-display]',
        toggleSelector: '[data-event-title-toggle]',
        formSelector: '[data-event-title-form]',
        inputSelector: '[data-event-title-input]',
        fieldName: 'nombre'
    });
    // Editor para la descripción corta
    initInlineEditor({
        containerSelector: '[data-description-editor]',
        displaySelector: '[data-description-display]',
        toggleSelector: '[data-description-toggle]',
        formSelector: '[data-description-form]',
        inputSelector: '[data-description-input]',
        fieldName: 'descripcion_corta'
    });
    // Editor para la descripción larga
    initInlineEditor({
        containerSelector: '[data-description_long-editor]',
        displaySelector: '[data-description_long-display]',
        toggleSelector: '[data-description_long-toggle]',
        formSelector: '[data-description_long-form]',
        inputSelector: '[data-description_long-input]',
        fieldName: 'descripcion_larga'
    });
    // Editor para la fecha
    initInlineEditor({
        containerSelector: '[data-date-editor]',
        displaySelector: '[data-date-display]',
        toggleSelector: '[data-date-toggle]',
        formSelector: '[data-date-form]',
        inputSelector: '[data-date-input]',
        fieldName: 'fecha'
    });
    // Editor para la hora
    initInlineEditor({
        containerSelector: '[data-hour-editor]',
        displaySelector: '[data-hour-display]',
        toggleSelector: '[data-hour-toggle]',
        formSelector: '[data-hour-form]',
        inputSelector: '[data-hour-input]',
        fieldName: 'hora'
    });
    // Puedes agregar más aquí, por ejemplo para artistas o precios
    // initInlineEditor({ ... });
});
