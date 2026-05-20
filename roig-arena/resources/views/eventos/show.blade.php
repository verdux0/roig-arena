@extends('layouts.app')

@section('title', $evento->nombre . ' | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
    <link rel="stylesheet" href="/css/pages/show.css">
@endsection

@section('content')
    <!-- Imagen hero del evento -->
    <div class="event-hero event-poster-editor"
        data-poster-editor
        data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}"
        data-current-poster-url="{{ $evento->poster_url }}"
        data-current-poster-ancho-url="{{ $evento->poster_ancho_url }}">
        @if($evento->poster_ancho_url)
            <img src="{{ $evento->poster_ancho_url }}" alt="{{ $evento->nombre }}" class="event-hero-image" data-poster-preview="poster_ancho_url">
        @else
            <div class="event-hero-image event-no-image" data-poster-preview="poster_ancho_url">
                Sin imagen disponible
            </div>
        @endif

        @auth
            @if(auth()->user()->isAdmin())
                <button type="button"
                        class="event-title-edit-button event-poster-edit-button"
                        data-poster-toggle="poster_ancho_url"
                        aria-label="Editar póster ancho">
                    ✎
                </button>
            @endif
        @endauth

        <div class="event-hero-overlay"></div>
    </div>
    <!-- POPUP DE SUBIDA DE IMAGENES EN JS -->
    @auth
        @if(auth()->user()->isAdmin())
            <div id="poster-modal" class="modal" hidden data-poster-modal>
                <div class="modal-backdrop" data-modal-backdrop></div>

                <div class="modal-panel modal-panel--wide" role="dialog" aria-modal="true" aria-label="Editar pósters del evento">
                    <header class="modal-header">
                        <h3>Editar pósters del evento</h3>
                        <button type="button" data-modal-close aria-label="Cerrar">✕</button>
                    </header>

                    <form class="modal-body" data-poster-form>
                        @csrf
                        @method('PATCH')

                        <div class="poster-field-group">
                            <label for="poster_url"><strong>URL del póster</strong></label>
                            <input
                                id="poster_url"
                                name="poster_url"
                                type="url"
                                placeholder="https://..."
                                class="poster-url-input"
                                data-poster-input="poster_url"
                            >
                        </div>

                        <div class="poster-field-group">
                            <label for="poster_ancho_url"><strong>URL del póster ancho</strong></label>
                            <input
                                id="poster_ancho_url"
                                name="poster_ancho_url"
                                type="url"
                                placeholder="https://..."
                                class="poster-url-input"
                                data-poster-input="poster_ancho_url"
                            >
                        </div>

                        <footer class="modal-footer">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <button type="button" class="btn btn-alt" data-modal-close>Cancelar</button>
                        </footer>
                    </form>
                </div>
            </div>
        @endif
    @endauth

    <!-- Información principal del evento -->
    <div class="event-info-grid">
        <!-- Información del evento -->
        <section class="event-info-section">
            <div class="event-title-editor" data-event-title-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
                <h2 class="event-title-display" data-event-title-display>{{ $evento->nombre }}</h2>

                @auth
                    @if(auth()->user()->isAdmin())
                        <button type="button" class="event-title-edit-button" data-event-title-toggle aria-label="Editar nombre del evento">
                            ✎
                        </button>

                        <form class="event-title-form" data-event-title-form hidden>
                            @csrf
                            @method('PATCH')
                            <div class="event-form-content">
                                <input type="text"
                                    name="nombre"
                                    value="{{ $evento->nombre }}"
                                    maxlength="255"
                                    required class="event-title-input"
                                    data-event-title-input
                                    aria-label="Nombre del evento"
                                >
                                <button type="submit" class="event-title-edit-button" aria-label="Guardar cambios">
                                    ✓
                                </button>
                            </div>
                        </form>
                    @endif
                @endauth
            </div>

            @if($evento->fecha || $evento->hora)
                <div class="event-meta-item event-date-editor" data-date-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
                    <span class="event-meta-label">Fecha</span>
                    <span class="event-meta-value" data-date-display>
                        @if($evento->fecha)
                            {{ $evento->fecha->format('d/m/Y') }}
                        @else
                            Por confirmar
                        @endif
                    </span>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <button type="button" class="event-title-edit-button" data-date-toggle aria-label="Editar fecha del evento">
                                ✎
                            </button>

                            <form class="event-date-form" data-date-form hidden>
                                @csrf
                                @method('PATCH')
                                <div class="event-form-content">
                                    <input
                                        type="date"
                                        name="fecha"
                                        value="{{ $evento->fecha ? $evento->fecha->format('Y-m-d') : '' }}"
                                        required
                                        class="event-date-input"
                                        data-date-input
                                        aria-label="Fecha del evento"
                                    >
                                    <button type="submit" class="event-title-edit-button" aria-label="Guardar cambios">
                                        ✓
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endauth
                </div>
            @endif
            <div class="event-meta-item event-hour-editor" data-hour-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
                <span class="event-meta-label">Hora</span>
                <span class="event-meta-value" data-hour-display>
                    @if($evento->hora)
                        {{ $evento->hora->format('H:i') }}
                    @else
                        Por confirmar
                    @endif
                </span>
                @auth
                    @if(auth()->user()->isAdmin())
                        <button type="button" class="event-title-edit-button" data-hour-toggle aria-label="Editar hora del evento">
                            ✎
                        </button>

                        <form class="event-hour-form" data-hour-form hidden>
                            @csrf
                            @method('PATCH')
                            <div class="event-form-content">
                                <input
                                    type="time"
                                    name="hora"
                                    value="{{ $evento->hora ? $evento->hora->format('H:i') : '' }}"
                                    required
                                    class="event-hour-input"
                                    data-hour-input
                                    aria-label="Hora del evento"
                                >
                                <button type="submit" class="event-title-edit-button" aria-label="Guardar cambios">
                                    ✓
                                </button>
                            </div>
                        </form>
                    @endif
                @endauth
            </div>

            @if($evento->descripcion_corta)
                <div class="event-description-block" data-description-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
                    <p class="event-description" data-description-display>{{ $evento->descripcion_corta }}</p>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <button type="button" class="event-title-edit-button" data-description-toggle aria-label="Editar descripción corta del evento">
                                ✎
                            </button>

                            <form class="event-description-form" data-description-form hidden>
                                @csrf
                                @method('PATCH')
                                <div class="event-form-content">
                                    <input
                                        type="text"
                                        name="descripcion_corta"
                                        value="{{ $evento->descripcion_corta }}"
                                        maxlength="255"
                                        required
                                        class="event-description-input"
                                        data-description-input
                                        aria-label="Descripción corta del evento"
                                    >
                                    <button type="submit" class="event-title-edit-button" aria-label="Guardar cambios">
                                        ✓
                                    </button>
                                </div>
                            </form>
                        @endif
                    @endauth
                </div>
            @endif
        </section>

        <!-- Artistas -->
        <section class="event-info-section">
            <div class="event-section-header">
                <h2>Artistas</h2>

                @auth
                    @if(auth()->user()->isAdmin())
                        <button
                            type="button"
                            class="btn btn-ghost btn-sm"
                            data-add-artista-button
                            aria-label="Añadir artista al evento"
                        >
                            <span aria-hidden="true">＋</span>
                            Añadir
                        </button>
                        <!-- Popup lista artistas -->
                        <div id="artista-modal" class="modal" hidden
                            data-modal
                            data-attach-url="{{ route('admin.eventos.artistas.store', ['eventoId' => $evento->id], false) }}"
                            data-detach-url-template="{{ route('admin.eventos.artistas.destroy', ['eventoId' => $evento->id, 'artistaId' => '__ID__'], false) }}"
                            data-existing-artistas='@json($evento->artistas->pluck("id"))'>
                            <div class="modal-backdrop" data-modal-backdrop></div>
                            <div class="modal-panel" role="dialog" aria-modal="true" aria-label="Seleccionar artistas">
                                <header class="modal-header">
                                    <h3>Agregar artistas al evento</h3>
                                    <button type="button" data-modal-close aria-label="Cerrar">✕</button>
                                </header>

                                <div class="modal-body">
                                    <div class="search-wrapper">
                                        <input type="search" id="artista-search" placeholder="Buscar artista..." />
                                        @auth
                                            @if(auth()->user()->isAdmin())
                                                <a href="{{ route('admin.artistas.create', [], false) }}" class="btn btn-primary" aria-label="+">+</a>
                                            @endif
                                        @endauth
                                    </div>
                                    <div id="artista-list" class="artista-list">
                                        <!-- Lista cargada por JS -->

                                    </div>
                                </div>

                                <footer class="modal-footer">
                                    <button type="button" data-modal-close class="btn btn-alt">Cerrar</button>
                                </footer>
                            </div>
                        </div>
                    @endif
                @endauth
            </div>

            @forelse($evento->artistas as $artista)
                <div class="artist-card">
                    <div class="artist-card-header">
                        @if($artista->imagen_url)
                            <img src="{{ $artista->imagen_url }}" alt="{{ $artista->nombre }}" class="artist-image">
                        @endif
                        <div>
                            <p class="artist-name">{{ $artista->nombre }}</p>
                            @if($artista->descripcion)
                                <p class="artist-description">{{ $artista->descripcion }}</p>
                            @endif
                        </div>
                    </div>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <form action="{{ route('admin.eventos.artistas.destroy', ['eventoId' => $evento->id, 'artistaId' => $artista->id], false) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="event-card-trash" aria-label="Quitar artista del evento" onclick="return confirm('¿Estás seguro de que quieres quitar este artista de este evento?')">
                                    🗑️
                                </button>
                            </form>
                        @endif
                    @endauth
                </div>
            @empty
                <p class="muted">No hay artistas asignados a este evento.</p>
            @endforelse
        </section>
    </div>

    <!-- Descripción detallada -->
    @if($evento->descripcion_larga)
        <section class="card section-gap" class="event-description_long-block" data-description_long-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
            <div class="section-divider">
                <span>SOBRE EL EVENTO</span>
            </div>
            <p class="event-description_long" data-description_long-display>{{ $evento->descripcion_larga }}</p>
            @auth
                @if(auth()->user()->isAdmin())
                    <button type="button" class="event-title-edit-button" data-description_long-toggle aria-label="Editar descripción larga del evento">
                        ✎
                    </button>

                    <form class="event-description_long-form" data-description_long-form hidden>
                        @csrf
                        @method('PATCH')
                        <div class="event-form-content">
                            <input
                                type="text"
                                name="descripcion_larga"
                                value="{{ $evento->descripcion_larga }}"
                                maxlength="255"
                                required
                                class="event-description_long-input"
                                data-description_long-input
                                aria-label="Descripción corta del evento"
                            >
                            <button type="submit" class="event-title-edit-button" aria-label="Guardar cambios">
                                ✓
                            </button>
                        </div>
                    </form>
                @endif
            @endauth
        </section>
    @endif

    <!-- Precios y disponibilidad -->
    <section class="card section-gap">
        <div class="section-divider">
            <span>PRECIOS</span>
        </div>
        <p class="muted event-prices-intro">Selecciona tu sector y compra tu entrada</p>
        @auth
            @if(auth()->user()->isAdmin())
                <!-- Popup lista sectores -->
                <div id="sector-modal" class="modal" hidden
                    data-modal
                    data-attach-url="{{ route('admin.eventos.sectores.store', ['eventoId' => $evento->id], false) }}"
                    data-detach-url-template="{{ route('admin.eventos.sectores.destroy', ['eventoId' => $evento->id, 'sectorId' => '__ID__'], false) }}"
                    data-existing-sectores='@json($evento->sectores->pluck("id"))'>
                    <div class="modal-backdrop" data-modal-backdrop></div>
                    <div class="modal-panel" role="dialog" aria-modal="true" aria-label="Seleccionar sectores">
                        <header class="modal-header">
                            <h3>Agregar sectores al evento</h3>
                            <button type="button" data-modal-close aria-label="Cerrar">✕</button>
                        </header>

                        <div class="modal-body">
                            <div class="search-wrapper">
                                <input type="search" id="sector-search" placeholder="Buscar sector..." />
                            </div>
                            <div id="sector-list" class="sector-list">
                                <!-- Lista cargada por JS -->

                            </div>
                        </div>

                        <footer class="modal-footer">
                            <button type="button" data-modal-close class="btn btn-alt">Cerrar</button>
                        </footer>
                    </div>
                </div>
            @endif
        @endauth

        <details class="pricing-table-dropdown">
            <summary class="pricing-table-summary">
                <span class="pricing-table-summary-text">Ver tabla de precios</span>
                <span class="pricing-table-summary-meta">{{ $precios->count() }} sectores disponibles</span>
            </summary>

            <div class="pricing-table-panel">
                <table class="pricing-table">
                    <thead>
                        <tr>
                            <th>Sector</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            @auth
                                @if(auth()->user()->isAdmin())
                                    <th>
                                        <span>Acciones</span>
                                        <span id="bulk-actions-controls" style="display: none; margin-left: 0.5rem;">
                                            <button type="button" class="event-card-trash" data-bulk-delete data-bulk-delete-url="{{ route('admin.precios.bulkDelete', [], false) }}" aria-label="Eliminar seleccionados">
                                                🗑️
                                            </button>
                                        </span>
                                    </th>
                                @endif
                            @endauth
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($precios as $precio)
                            <tr>
                                <td>
                                    <strong>{{ $precio->sector->nombre }}</strong>
                                    @if($precio->sector->descripcion)
                                        <br>
                                        <span class="muted">{{ $precio->sector->descripcion }}</span>
                                    @endif
                                </td>
                                <td class="price-highlight">
                                    <span id="sector-price-display-{{ $precio->id }}" data-sector-price-display>
                                        {{ number_format($precio->precio, 2, ',', '.') }}€
                                    </span>
                                </td>
                                <td>
                                    @if($precio->disponible)
                                        <span class="badge badge-success">Disponible</span>
                                    @else
                                        <span class="badge badge-danger">Agotado</span>
                                    @endif
                                </td>
                                @auth
                                    @if(auth()->user()->isAdmin())
                                        <td class="pricing-table-actions">
                                            <form action="{{ route('admin.sectores.disable', ['id' => $precio->id], false) }}" method="POST" style="display: inline;" data-row-delete-form>
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="event-card-trash" data-row-delete-button aria-label="Eliminar sector" onclick="return confirm('¿Estás seguro de que quieres desactivar este sector?')">
                                                    🗑️
                                                </button>
                                            </form>
                                            <button type="button" class="event-title-edit-button" data-row-edit-button data-sector_price-toggle aria-label="Editar precio del sector">
                                                ✎
                                            </button>
                                            <form
                                                class="event-sector_price-form"
                                                data-sector-price-editor
                                                data-sector-price-display="#sector-price-display-{{ $precio->id }}"
                                                action="{{ route('admin.precios.update', ['id' => $precio->id], false) }}"
                                                method="POST"
                                                hidden
                                            >
                                                @csrf
                                                @method('PATCH')
                                                <input
                                                    type="number"
                                                    name="precio"
                                                    value="{{ number_format((float) $precio->precio, 2, '.', '') }}"
                                                    min="0"
                                                    step="0.01"
                                                    required
                                                    class="event-sector_price-input"
                                                    data-sector-price-input
                                                    aria-label="Precio del sector {{ $precio->sector->nombre }}"
                                                >
                                            </form>

                                            <label for="sector-price-select-{{ $precio->id }}" style="display: inline-flex; align-items: center; gap: 0.35rem; margin-right: 0.75rem;">
                                                <input
                                                    type="checkbox"
                                                    id="sector-price-select-{{ $precio->id }}"
                                                    name="precios_seleccionados[]"
                                                    value="{{ $precio->id }}"
                                                    data-sector-price-checkbox
                                                    data-sector-id="{{ $precio->sector_id }}"
                                                    data-precio-id="{{ $precio->id }}"
                                                >
                                            </label>
                                        </td>
                                    @endif
                                @endauth
                            </tr>
                        @endforeach
                        @auth
                            @if(auth()->user()->isAdmin())
                                <tr class="pricing-table-add-row">
                                    <td colspan="4">
                                        <button type="button" class="btn btn-primary btn-add-sector-row" data-add-sector-button aria-label="Agregar sectores al evento">
                                            + Agregar sectores al evento
                                        </button>
                                    </td>
                                </tr>
                            @endif
                        @endauth
                    </tbody>
                </table>
            </div>
        </details>

        @if($evento->sectoresDisponibles()->count() > 0)
            <div class="cta-section">
                <a href="{{ route('compra.buy', ['evento' => $evento->id], false) }}" class="btn btn-primary">
                    Comprar Entradas
                </a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.eventos.sectores.editor', ['eventoId' => $evento->id], false) }}" class="btn btn-primary">
                            Editar Sectores
                        </a>
                    @endif
                @endauth
                <a href="{{ route('eventos.index', [], false) }}" class="btn btn-alt">
                    Volver
                </a>
            </div>
        @else
            <div class="cta-section">
                <p class="muted">No hay entradas disponibles para este evento en este momento.</p>
                <a href="{{ route('eventos.index', [], false) }}" class="btn btn-alt">
                    Volver al listado
                </a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.eventos.sectores.editor', ['eventoId' => $evento->id], false) }}" class="btn btn-primary">
                            Editar Sectores
                        </a>
                    @endif
                @endauth
            </div>
        @endif
    </section>

@endsection

@section('page_scripts')
    @auth
        @if(auth()->user()->isAdmin())
            <script src="/js/pages/updateFieldText.js"></script>
            <script src="/js/pages/updateFieldPrice.js"></script>
            <script src="/js/pages/multiDelete.js"></script>
            <script src="/js/pages/popUps.js"></script>
            <script src="/js/pages/popUpSectores.js"></script>
            <script src="/js/pages/posterEditor.js"></script>
        @endif
    @endauth
@endsection
