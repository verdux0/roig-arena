@extends('layouts.app')

@section('title', 'Crear evento | Roig Arena')

@section('content')
    <section class="card">
        <h1>Crear evento</h1>
        <p class="muted">Formulario base para alta de eventos.</p>

        @if (session('success'))
            <div class="card" style="margin-top: 1rem; border-color: #2e7d32;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="card" style="margin-top: 1rem; border-color: #d9534f;">
                <strong>Revisa los siguientes errores:</strong>
                <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.eventos.store', [], false) }}" style="display: grid; gap: 1rem; margin-top: 1rem;">
            @csrf

            <div>
                <label for="nombre"><strong>Nombre del evento</strong></label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div>
                <label for="descripcion_corta"><strong>Descripción corta</strong></label>
                <input id="descripcion_corta" name="descripcion_corta" type="text" value="{{ old('descripcion_corta') }}" maxlength="255" required style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div>
                <label for="descripcion_larga"><strong>Descripción larga</strong></label>
                <textarea id="descripcion_larga" name="descripcion_larga" rows="4" required style="display:block;width:100%;margin-top:.35rem;">{{ old('descripcion_larga') }}</textarea>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;">
                <div>
                    <label for="fecha"><strong>Fecha</strong></label>
                    <input id="fecha" name="fecha" type="date" value="{{ old('fecha') }}" required style="display:block;width:100%;margin-top:.35rem;">
                </div>

                <div>
                    <label for="hora"><strong>Hora</strong></label>
                    <input id="hora" name="hora" type="time" value="{{ old('hora') }}" style="display:block;width:100%;margin-top:.35rem;">
                </div>
            </div>

            <div>
                <label for="poster_url"><strong>URL del póster</strong></label>
                <input id="poster_url" name="poster_url" type="url" value="{{ old('poster_url') }}" placeholder="https://..." style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div>
                <label for="poster_ancho_url"><strong>URL del póster ancho</strong></label>
                <input id="poster_ancho_url" name="poster_ancho_url" type="url" value="{{ old('poster_ancho_url') }}" placeholder="https://..." style="display:block;width:100%;margin-top:.35rem;">
            </div>

            @php
                $oldSectores = old('sectores', []);
                $oldPrecios = old('precios', []);
                $oldSelectedSectors = collect($oldSectores)->map(function ($sectorId, $index) use ($sectoresDisponibles, $oldPrecios) {
                    $sector = $sectoresDisponibles->firstWhere('id', $sectorId);
                    return [
                        'id' => $sectorId,
                        'nombre' => $sector ? $sector->nombre : "Sector {$sectorId}",
                        'precio' => $oldPrecios[$index] ?? '',
                    ];
                });
            @endphp

            <div class="card" style="padding:1rem;border:1px solid #ddd;margin-top:1rem;">
                <h2 style="margin:0 0 .5rem;">Sectores disponibles</h2>
                <p class="muted">Selecciona los sectores que estarán disponibles en este evento y asigna su precio a cada uno.</p>

                <div style="display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:end;margin-top:1rem;">
                    <div>
                        <label for="sector_selector"><strong>Sector</strong></label>
                        <select id="sector_selector" style="display:block;width:100%;margin-top:.35rem;">
                            <option value="">Selecciona un sector</option>
                            @foreach ($sectoresDisponibles as $sector)
                                <option value="{{ $sector->id }}" data-nombre="{{ $sector->nombre }}">{{ $sector->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" id="addSectorButton" class="btn">Añadir sector</button>
                </div>

                <div id="selectedSectorsContainer" style="margin-top:1rem;display:grid;gap:.75rem;"></div>
                <div id="sectorEmptyNotice" style="display:none;margin-top:1rem;color:#555;font-size:.95rem;">
                    No has añadido ningún sector todavía.
                </div>
            </div>

            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ route('eventos.index', [], false) }}" class="btn">Cancelar</a>
            </div>
        </form>
    </section>
@endsection

@section('page_scripts')
    <script>
        (function () {
            const sectorSelect = document.getElementById('sector_selector');
            const addSectorButton = document.getElementById('addSectorButton');
            const selectedContainer = document.getElementById('selectedSectorsContainer');
            const emptyNotice = document.getElementById('sectorEmptyNotice');

            const initialSelected = @json($oldSelectedSectors->values());

            const itemTemplate = function ({ id, nombre, precio }) {
                const wrapper = document.createElement('div');
                wrapper.style.display = 'grid';
                wrapper.style.gridTemplateColumns = '1fr auto';
                wrapper.style.gap = '1rem';
                wrapper.style.alignItems = 'center';
                wrapper.style.padding = '.75rem';
                wrapper.style.border = '1px solid #e0e0e0';
                wrapper.style.borderRadius = '6px';

                wrapper.innerHTML = `
                    <div>
                        <strong class="sector-name"></strong>
                        <input type="hidden" name="sectores[]" class="sector-id-input">
                    </div>
                    <div style="display:grid;grid-template-columns:150px auto;gap:.75rem;align-items:end;">
                        <label style="display:block;font-size:.9rem;color:#333;">Precio (€)</label>
                        <input type="number" step="0.01" min="0" name="precios[]" class="sector-price-input" required style="width:100%;padding:.5rem;border:1px solid #ccc;border-radius:4px;">
                        <button type="button" class="remove-sector btn" style="padding:.5rem .85rem;">Quitar</button>
                    </div>
                `;

                wrapper.querySelector('.sector-name').textContent = nombre;
                wrapper.querySelector('.sector-id-input').value = id;
                wrapper.querySelector('.sector-price-input').value = precio ?? '';

                wrapper.querySelector('.remove-sector').addEventListener('click', function () {
                    restoreSectorOption(id, nombre);
                    wrapper.remove();
                    toggleEmptyNotice();
                });

                return wrapper;
            };

            function toggleEmptyNotice() {
                emptyNotice.style.display = selectedContainer.children.length ? 'none' : 'block';
            }

            function removeSectorOption(id) {
                const option = sectorSelect.querySelector(`option[value="${id}"]`);
                if (option) {
                    option.remove();
                }
            }

            function restoreSectorOption(id, nombre) {
                if (sectorSelect.querySelector(`option[value="${id}"]`)) {
                    return;
                }
                const option = document.createElement('option');
                option.value = id;
                option.dataset.nombre = nombre;
                option.textContent = nombre;
                sectorSelect.appendChild(option);
            }

            function addSector({ id, nombre, precio }) {
                selectedContainer.appendChild(itemTemplate({ id, nombre, precio }));
                removeSectorOption(id);
                sectorSelect.value = '';
                toggleEmptyNotice();
            }

            addSectorButton.addEventListener('click', function () {
                const selectedId = sectorSelect.value;
                if (!selectedId) {
                    return;
                }
                const selectedOption = sectorSelect.selectedOptions[0];
                addSector({
                    id: selectedId,
                    nombre: selectedOption.dataset.nombre || selectedOption.textContent,
                    precio: '',
                });
            });

            initialSelected.forEach(function (sector) {
                addSector(sector);
            });

            toggleEmptyNotice();
        })();
    </script>
@endsection
