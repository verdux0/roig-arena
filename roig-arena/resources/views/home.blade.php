@extends('layouts.app')

@section('title', 'Inicio | Roig Arena')
@section('body_class', 'home-page')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/home.css">
@endsection

@section('content')
    <section class="home-hero" aria-label="Portada principal">
        <p class="home-hero-kicker">Roig Arena Valencia</p>
        <h1 class="home-hero-title">Bienvenido al Roig Arena</h1>
        <p class="home-hero-text">
            Disfruta de conciertos, espectaculos y eventos unicos en una experiencia pensada para vivir
            cada detalle al maximo.
        </p>

        <nav class="home-hero-actions" aria-label="Acciones principales">
            <a class="btn" href="{{ route('eventos.index', [], false) }}">Ver eventos</a>
            @guest
                <a class="btn btn-alt" href="{{ route('login', [], false) }}">Iniciar sesión</a>
            @endguest
        </nav>
    </section>
@endsection
