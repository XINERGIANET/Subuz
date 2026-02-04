@extends('template.app')

@section('title', 'Editar despachador')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.dispatchers.index') }}">Despachadores</a></li>
    <li class="breadcrumb-item active">Editar</li>
  </ol>
</nav>

<div class="card">
	<div class="card-body">
		<form method="POST" action="{{ route('users.dispatchers.update', $dispatcher) }}">
			@csrf
			@method('PUT')
			<div class="row">
				<div class="col-lg-6">
					<div class="mb-3">
						<label class="form-label">Nombre</label>
						<input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $dispatcher->name) }}">
						@error('name')
						<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-lg-6">
					<div class="mb-3">
						<label class="form-label">Usuario</label>
						<input type="text" class="form-control @error('user') is-invalid @enderror" name="user" value="{{ old('user', $dispatcher->user) }}">
						@error('user')
						<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-lg-6">
					<div class="mb-3">
						<label class="form-label">Nueva contrase&ntilde;a (opcional)</label>
						<input type="password" class="form-control @error('password') is-invalid @enderror" name="password">
						@error('password')
						<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-lg-6">
					<div class="mb-3">
						<label class="form-label">Confirmar contrase&ntilde;a</label>
						<input type="password" class="form-control" name="password_confirmation">
					</div>
				</div>
			</div>
			<button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i> Guardar</button>
		</form>
	</div>
</div>
@endsection
