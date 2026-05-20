function formatPrice(value) {
    const numericValue = Number.parseFloat(value);

    if (Number.isNaN(numericValue)) {
        return value;
    }

    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numericValue) + '€';
}

function initPriceInlineEditors() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    if (!csrfToken) return;

    document.querySelectorAll('[data-sector-price-editor]').forEach((form) => {
        if (form.dataset.priceEditorInitialized === 'true') return;

        const container = form.closest('td, .pricing-table-actions, .event-price-editor') || form.parentElement;
        const toggleButton = container?.querySelector('[data-sector_price-toggle], [data-sector-price-toggle]');
        const input = form.querySelector('[data-sector-price-input]');
        const displaySelector = form.dataset.sectorPriceDisplay;
        const display = displaySelector ? document.querySelector(displaySelector) : container?.querySelector('[data-sector-price-display]');

        if (!toggleButton || !input || !display || !form.action) return;

        const openEditor = () => {
            form.hidden = false;
            toggleButton.hidden = true;
            input.focus();
            input.select();
        };

        const closeEditor = () => {
            form.hidden = true;
            toggleButton.hidden = false;
        };

        toggleButton.addEventListener('click', openEditor);

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                input.value = input.defaultValue;
                closeEditor();
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const newValue = input.value.trim();
            if (!newValue) {
                input.setCustomValidity('El precio no puede estar vacío.');
                input.reportValidity();
                return;
            }

            try {
                const formData = new FormData(form);
                formData.set('precio', newValue);
                formData.set('_method', 'PATCH');

                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'include',
                    mode: 'cors',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = payload?.message || payload?.error || 'No se pudo actualizar el precio.';
                    input.setCustomValidity(message);
                    input.reportValidity();
                    input.focus();
                    return;
                }

                const updatedPrice = payload?.data?.precio ?? newValue;
                display.textContent = formatPrice(updatedPrice);
                input.defaultValue = Number.parseFloat(updatedPrice).toFixed(2);
                input.value = input.defaultValue;
                closeEditor();
            } catch (error) {
                input.setCustomValidity('Error de red al actualizar el precio.');
                input.reportValidity();
            }
        });

        form.dataset.priceEditorInitialized = 'true';
    });
}

document.addEventListener('DOMContentLoaded', initPriceInlineEditors);
window.initPriceInlineEditors = initPriceInlineEditors;
