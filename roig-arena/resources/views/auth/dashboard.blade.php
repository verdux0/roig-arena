@extends('layouts.app')

@section('title', 'Perfil | Roig Arena')
@section('body_class', 'dashboard-page')

@section('page_styles')
	<link rel="stylesheet" href="/css/pages/dashboard.css">
@endsection

@section('content')
    @php
		$redirectTo = request('redirect', route('home', [], false));
	@endphp

    <section class="dashboard-shell" aria-label="Perfil de usuario">
        <h1 class="dashboard-title">Bienvenido, {{ auth()->user()->nombre }} {{ auth()->user()->apellido }}</h1>
        <p class="dashboard-text">
            Desde tu perfil puedes gestionar tus datos personales, revisar tus compras y acceder a tus entradas.
        </p>

        <div class="dashboard-actions">
            <a href="{{ route('profile', [], false) }}" class="dashboard-action">Mi perfil</a>
            <a href="{{ route('mis-eventos', [], false) }}" class="dashboard-action">Mis eventos</a>
            <a href="{{ route('mis-pagos-pendientes', [], false) }}" class="dashboard-action">Pagos Pendientes</a>
            <form method="POST" class="dashboard-action-form" action="{{ route('logout.post', [], false) }}">
                @csrf
                <button class="dashboard-action dashboard-action--danger" type="submit">Cerrar sesión</button>
            </form>
        </div>
    </section>
@endsection
