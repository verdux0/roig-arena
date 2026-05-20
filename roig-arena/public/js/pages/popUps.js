/**
 * Popup para añadir artistas a eventos
 */

document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.querySelector('[data-add-artista-button]');
    const modal = document.querySelector('#artista-modal');

    if (!addBtn || !modal) return;

    const backdrop = modal.querySelector('[data-modal-backdrop]');
    const closeButtons = modal.querySelectorAll('[data-modal-close]');
    const listEl = modal.querySelector('#artista-list');
    const searchInput = modal.querySelector('#artista-search');
    const searchBtn = modal.querySelector('#artista-search-btn');
    const attachUrl = modal.getAttribute('data-attach-url') || '';
    const detachUrlTemplate = modal.getAttribute('data-detach-url-template') || '';
    const existing = JSON.parse(modal.getAttribute('data-existing-artistas') || '[]').map(Number);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function openModal() {
        modal.hidden = false;
        if (searchInput) searchInput.focus();
        fetchArtistas();
    }

    function closeModal() {
        modal.hidden = true;
        if (listEl) listEl.innerHTML = '';
        if (searchInput) searchInput.value = '';
    }

    function fetchArtistas(q = '') {
        if (!listEl) return;

        listEl.innerHTML = '<p class="muted">Cargando artistas...</p>';

        const url = q
            ? '/api/artistas/buscar?q=' + encodeURIComponent(q)
            : '/api/artistas';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const artistas = Array.isArray(data.data) ? data.data : [];
                if (artistas.length === 0) {
                    listEl.innerHTML = '<p class="muted">No hay artistas disponibles.</p>';
                    return;
                }
                renderList(artistas);
            })
            .catch(error => {
                console.error(error);
                listEl.innerHTML = '<p class="muted">Error cargando artistas.</p>';
            });
    }

    function isAssociated(id) {
        return existing.includes(Number(id));
    }

    function getDetachUrl(id) {
        return detachUrlTemplate ? detachUrlTemplate.replace('__ID__', id) : '';
    }

    function renderList(artistas) {
        listEl.innerHTML = '';

        artistas.forEach(item => {
            const artista = item.data ? item.data : item;
            const id = Number(artista.id);
            const associated = isAssociated(id);

            const card = document.createElement('div');
            card.className = 'artist-row';
            card.dataset.artistaId = String(id);

            card.innerHTML = `
                <div class="artist-info">
                    ${artista.imagen_url ? `<img src="${artista.imagen_url}" alt="${escapeHtml(artista.nombre)}" class="artist-thumb">` : ''}
                    <div>
                        <div class="artist-name">${escapeHtml(artista.nombre)}</div>
                        ${artista.descripcion ? `<div class="artist-desc muted">${escapeHtml(artista.descripcion)}</div>` : ''}
                    </div>
                </div>
                <div class="artist-actions">
                    <button type="button" class="btn btn-sm btn-primary add-artista-btn" data-artista-id="${id}" ${associated ? 'disabled' : ''}>
                        ${associated ? 'Añadido' : 'Añadir'}
                    </button>
                    ${`<button type="button" class="btn btn-sm btn-danger delete-artista-btn" data-artista-id="${id}">Borrar</button>`}
                </div>
            `;

            listEl.appendChild(card);
        });

        listEl.querySelectorAll('.add-artista-btn').forEach(btn => {
            btn.addEventListener('click', onAddArtista);
        });

        listEl.querySelectorAll('.delete-artista-btn').forEach(btn => {
            btn.addEventListener('click', onDeleteArtista);
        });
    }

    function markRowAsAssociated(row) {
        const addBtn = row.querySelector('.add-artista-btn');
        if (addBtn) {
            addBtn.disabled = true;
            addBtn.textContent = 'Añadido';
        }

        if (!row.querySelector('.delete-artista-btn')) {
            const id = row.getAttribute('data-artista-id');
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn btn-sm btn-danger delete-artista-btn';
            deleteBtn.setAttribute('data-artista-id', id || '');
            deleteBtn.textContent = 'Borrar';
            deleteBtn.addEventListener('click', onDeleteArtista);
            row.querySelector('.artist-actions')?.appendChild(deleteBtn);
        }
    }

    function onAddArtista(e) {
        e.preventDefault();

        const btn = e.currentTarget;
        const id = Number(btn.getAttribute('data-artista-id'));
        if (!id || !attachUrl) return;

        btn.disabled = true;
        btn.textContent = 'Añadiendo...';

        fetch(attachUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ artista_id: id }),
        })
            .then(response => response.json().then(body => ({ status: response.status, body })))
            .then(({ status, body }) => {
                if (status < 200 || status >= 300) {
                    throw new Error(body.message || body.error || 'Error al añadir artista');
                }

                if (!existing.includes(id)) {
                    existing.push(id);
                }

                const row = btn.closest('.artist-row');
                if (row) {
                    markRowAsAssociated(row);
                    appendArtistCardToPage(id, row);
                }
            })
            .catch(error => {
                console.error(error);
                btn.disabled = false;
                btn.textContent = 'Añadir';
                alert(error.message || 'Error de red al añadir artista');
            });
    }

    function onDeleteArtista(e) {
        e.preventDefault();

        if (!confirm('¿Estás seguro de que quieres borrar definitivamente este artista?')) return;

        const btn = e.currentTarget;
        const id = Number(btn.getAttribute('data-artista-id'));
        if (!id) return;

        btn.disabled = true;
        btn.textContent = 'Borrando...';

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'DELETE');

        fetch(`/admin/artistas/${id}`, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
            },
        })
            .then(response => response.json().then(body => ({ status: response.status, body })))
            .then(({ status, body }) => {
                if (status < 200 || status >= 300) {
                    throw new Error(body.message || body.error || 'Error al borrar artista');
                }

                const index = existing.indexOf(id);
                if (index > -1) existing.splice(index, 1);

                removeArtistCardFromPage(id);

                const row = btn.closest('.artist-row');
                if (row) {
                    row.remove();
                }
            })
            .catch(error => {
                console.error(error);
                btn.disabled = false;
                btn.textContent = 'Borrar';
                alert(error.message || 'Error de red al borrar artista');
            });
    }

    function removeArtistCardFromPage(artistaId) {
        const artistsSection = document.querySelector('.event-info-section:nth-of-type(2)');
        if (!artistsSection) return;

        const cards = Array.from(artistsSection.querySelectorAll('.artist-card'));
        cards.forEach(card => {
            const cardId = Number(card.getAttribute('data-artista-id'));
            const form = card.querySelector('form');

            if (cardId && cardId === Number(artistaId)) {
                card.remove();
                return;
            }

            if (form && form.action.includes(`/artistas/${artistaId}`)) {
                card.remove();
            }
        });

        if (!artistsSection.querySelector('.artist-card')) {
            const existingEmpty = artistsSection.querySelector('.muted');
            if (!existingEmpty) {
                const emptyMsg = document.createElement('p');
                emptyMsg.className = 'muted';
                emptyMsg.textContent = 'No hay artistas asignados a este evento.';
                artistsSection.appendChild(emptyMsg);
            }
        }
    }

    function appendArtistCardToPage(artistaId, row) {
        const artistsSection = document.querySelector('.event-info-section:nth-of-type(2)');
        if (!artistsSection || !row) return;

        if (artistsSection.querySelector(`.artist-card[data-artista-id="${artistaId}"]`)) {
            return;
        }

        const nameEl = row.querySelector('.artist-name');
        const imgEl = row.querySelector('img');
        const descEl = row.querySelector('.artist-desc');
        const deleteUrl = getDetachUrl(artistaId);

        const emptyMsg = artistsSection.querySelector('.muted');
        if (emptyMsg) emptyMsg.remove();

        const card = document.createElement('div');
        card.className = 'artist-card';
        card.setAttribute('data-artista-id', String(artistaId));

        card.innerHTML = `
            <div class="artist-card-header">
                ${imgEl ? `<img src="${imgEl.src}" alt="${escapeHtml(nameEl ? nameEl.textContent : '')}" class="artist-image">` : ''}
                <div>
                    <p class="artist-name">${escapeHtml(nameEl ? nameEl.textContent : '')}</p>
                    ${descEl ? `<p class="artist-description">${escapeHtml(descEl.textContent)}</p>` : ''}
                </div>
            </div>
        `;

        if (deleteUrl) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = deleteUrl;
            form.style.display = 'inline';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = csrfToken;
            form.appendChild(tokenInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);

            const deleteButton = document.createElement('button');
            deleteButton.type = 'submit';
            deleteButton.className = 'event-card-trash';
            deleteButton.setAttribute('aria-label', 'Quitar artista del evento');
            deleteButton.textContent = 'Borrar';

            form.appendChild(deleteButton);
            card.appendChild(form);
        }

        artistsSection.appendChild(card);
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    addBtn.addEventListener('click', openModal);
    backdrop?.addEventListener('click', closeModal);
    closeButtons.forEach(button => button.addEventListener('click', closeModal));

    if (searchInput) {
        let timeoutId;
        searchInput.addEventListener('input', function () {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                fetchArtistas(searchInput.value.trim());
            }, 300);
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function (e) {
            e.preventDefault();
            fetchArtistas(searchInput ? searchInput.value.trim() : '');
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) {
            closeModal();
        }
    });
});
