@extends('layouts.app')

@section('title', 'Registrarse | Roig Arena')
@section('body_class', 'register-page')

@section('page_styles')
	<link rel="stylesheet" href="/css/pages/register.css">
@endsection

@section('content')
	@php
		$redirectTo = request('redirect', url()->previous());
	@endphp

	<section class="register-shell" aria-label="Registro de usuario">
		<div class="register-copy">
			<p class="register-kicker">Registro de usuario</p>
			<h1 class="register-title">Registrate para comprar entradas y gestionar tus asientos</h1>
			<p class="register-text">
				Registrate con tu correo y contraseña. Guardaremos tu token de Sanctum para que puedas continuar
				con la compra de entradas sin perder la selección.
			</p>
		</div>

		<div class="register-card">
			<form
				class="register-form"
				id="registerForm"
				method="POST"
				action="{{ route('register.post', [], false) }}"
				data-redirect-to="{{ $redirectTo }}"
				novalidate
			>
                <label class="register-field" for="nombre">
                    <span>Nombre</span>
                    <input
                        id="nombre"
                        type="text"
                        name="nombre"
                        placeholder="Tu nombre"
                        autocomplete="nombre"
                        required
                        autofocus
                    >
                    <p class="register-error" data-error-for="nombre"></p>
                </label>

                <label class="register-field" for="apellido">
                    <span>Apellido</span>
                    <input
                        id="apellido"
                        type="text"
                        name="apellido"
                        placeholder="Tu apellido"
                        autocomplete="family-name"
                        required
                    >
                    <p class="register-error" data-error-for="apellido"></p>
                </label>

				<label class="register-field" for="email">
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
					<p class="register-error" data-error-for="email"></p>
				</label>

				<label class="register-field" for="password">
					<span>Contraseña</span>
					<input
						id="password"
						type="password"
						name="password"
						placeholder="Tu contraseña"
						autocomplete="current-password"
						required
					>
					<p class="register-error" data-error-for="password"></p>
				</label>

                <label class="register-field" for="password_confirmation">
                    <span>Confirmar contraseña</span>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        placeholder="Confirma tu contraseña"
                        autocomplete="current-password"
                        required
                    >
                    <p class="register-error" data-error-for="password_confirmation"></p>
                </label>

				<div class="register-alert" id="registerAlert" hidden></div>

				<button class="register-submit" type="submit" id="registerSubmit">Registrarse</button>

				<p class="register-note">
					¿Ya tienes una cuenta?
					<a href="{{ route('login', array_filter(['redirect' => $redirectTo]), false) }}">Iniciar sesión</a>
				</p>

                <p class="register-note">
					¿Quieres volver a ver los eventos?
					<a href="{{ route('eventos.index', [], false) }}">Ir al listado</a>
				</p>
			</form>
		</div>
	</section>

	<script src="/js/pages/register.js"></script>
@endsection
