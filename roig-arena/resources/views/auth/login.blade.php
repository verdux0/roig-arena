@extends('layouts.app')

@section('title', 'Iniciar sesión | Roig Arena')
@section('body_class', 'login-page')

@section('page_styles')
	<link rel="stylesheet" href="/css/pages/login.css">
@endsection

@section('content')
	@php
		$redirectTo = request('redirect', url()->previous());
	@endphp

	<section class="login-shell" aria-label="Acceso de usuario">
		<div class="login-copy">
			<p class="login-kicker">Acceso de usuario</p>
			<h1 class="login-title">Entra para comprar entradas y gestionar tus asientos</h1>
			<p class="login-text">
				Inicia sesión con tu correo y contraseña para acceder a tu perfil y continuar tu compra.
			</p>
		</div>

		<div class="login-card">
			<form
				class="login-form"
				id="loginForm"
				method="POST"
				action="{{ route('login.post', [], false) }}"
				data-redirect-to="{{ $redirectTo }}"
				novalidate
			>
				<label class="login-field" for="email">
					<span>Correo electrónico</span>
					<input
						id="email"
						type="email"
						name="email"
						placeholder="usuario@correo.com"
						autocomplete="email"
						required
						autofocus
					>
					<p class="login-error" data-error-for="email"></p>
				</label>

				<label class="login-field" for="password">
					<span>Contraseña</span>
					<input
						id="password"
						type="password"
						name="password"
						placeholder="Tu contraseña"
						autocomplete="current-password"
						required
					>
					<p class="login-error" data-error-for="password"></p>
				</label>

				<div class="login-alert" id="loginAlert" hidden></div>

				<button class="login-submit" type="submit" id="loginSubmit">Entrar</button>

                <p class="login-note">
                    ¿No tienes una cuenta?
					<a href="{{ route('register', array_filter(['redirect' => $redirectTo]), false) }}">Regístrate</a>
                </p>

				<p class="login-note">
					¿Quieres volver a ver los eventos?
					<a href="{{ route('eventos.index', [], false) }}">Ir al listado</a>
				</p>
			</form>
		</div>
	</section>

	<script src="/js/pages/login.js"></script>
@endsection
