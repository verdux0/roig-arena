@extends('layouts.app')

@section('title', 'Crear artista | Roig Arena')

@section('content')
    <section class="card">
        <h1>Crear artista</h1>
        <p class="muted">Formulario base para alta de artistas.</p>

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

        <form method="POST" action="{{ route('admin.artistas.store', [], false) }}" style="display: grid; gap: 1rem; margin-top: 1rem;">
            @csrf

            <div>
                <label for="nombre"><strong>Nombre del artista</strong></label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div>
                <label for="descripcion"><strong>Descripción</strong></label>
                <input id="descripcion" name="descripcion" type="text" value="{{ old('descripcion') }}" maxlength="255" required style="display:block;width:100%;margin-top:.35rem;">
            </div>

            <div>
                <label for="imagen_url"><strong>Imagen</strong></label>
                <input id="imagen_url" name="imagen_url" type="url" value="{{ old('imagen_url') }}" placeholder="https://..." style="display:block;width:100%;margin-top:.35rem;">
            </div>

            {{-- Sectores eliminados del formulario de creación de artistas --}}

            <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="{{ url()->previous() }}" class="btn">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
