@extends('layouts.app')

@section('title', 'Mis Eventos | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
@endsection

@section('content')
    <div class="eventos-header">
        <h1>Mis Eventos</h1>
        <p class="muted no-margin">Próximos conciertos y espectáculos en el Roig Arena.</p>
    </div>

    <section class="grid grid-gap-bottom">
        @forelse ($miseventos as $evento)
                <a href="{{ route('mi-evento.info', ['id' => $evento->id], false) }}" class="event-card">
                    <img src="{{ $evento->poster_url }}" alt="{{ $evento->nombre }}" class="event-card-image">
                    <div class="event-card-body">
                        <h2 class="event-card-title">{{ $evento->nombre }}</h2>
                        <p class="event-meta">
                            <strong>{{ optional($evento->fecha)->format('d/m/Y') }}</strong>
                            @if($evento->hora) · {{ optional($evento->hora)->format('H:i') }} @endif
                        </p>
                    </div>
                </a>
        @empty
            <article class="card">
                <p class="no-margin">No hay eventos disponibles.</p>
            </article>
        @endforelse
    </section>

    <section class="card">
        <div class="card-body">
            <h2>¿Quieres ver todas las entradas?</h2>
            <p>Haz clic en el botón de abajo para ver las entradas de cada evento, incluyendo los asientos reservados.</p>
            <a href="{{ route('mis-eventos.info', [], false) }}" class="button">Ver Todas las Entradas</a>
        </div>
    </section>
@endsection
