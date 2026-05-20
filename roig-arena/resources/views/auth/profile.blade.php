@extends('layouts.app')

@section('title', 'Mi perfil | Roig Arena')
@section('body_class', 'dashboard-page')

@section('page_styles')
	<link rel="stylesheet" href="/css/pages/dashboard.css">
@endsection

@section('content')
	@php
		$user = auth()->user();
	@endphp

	<section class="dashboard-shell" aria-label="Datos del perfil">
		<h1 class="dashboard-title">Mi perfil</h1>

		@if ($user)
			<p class="dashboard-text">
				Consulta tus datos de cuenta y accede a las acciones principales de tu zona personal.
			</p>

			<div class="dashboard-text" style="margin-bottom:1.25rem;">
				<strong>Nombre:</strong> {{ $user->nombre }}<br>
				<strong>Apellidos:</strong> {{ $user->apellido }}<br>
				<strong>Email:</strong> {{ $user->email }}<br>
				<strong>Rol:</strong> {{ $user->is_admin ? 'Administrador' : 'Cliente' }}
			</div>

			<div class="dashboard-actions">
				<a href="{{ route('dashboard', [], false) }}" class="dashboard-action">Volver al panel</a>
				<a href="{{ route('eventos.index', [], false) }}" class="dashboard-action">Ver eventos</a>

				<form method="POST" class="dashboard-action-form" action="{{ route('logout.post', [], false) }}">
					@csrf
					<button class="dashboard-action dashboard-action--danger" type="submit">Cerrar sesión</button>
				</form>
			</div>
		@else
			<p class="dashboard-text">
				Tu sesión no está activa. Inicia sesión para acceder a tu perfil.
			</p>
			<div class="dashboard-actions">
				<a href="{{ route('login', [], false) }}" class="dashboard-action">Ir a login</a>
			</div>
		@endif
	</section>
@endsection
