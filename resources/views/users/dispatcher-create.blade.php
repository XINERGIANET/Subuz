@extends('template.app')

@section('title', 'Crear despachador')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Usuarios</li>
  </ol>
</nav>

<div class="card">
	<div class="card-body">
		<form method="POST" action="{{ route('users.dispatcher.store') }}">
			@csrf
			<div class="row">
				<div class="col-lg-6">
					<div class="mb-3">
						<label class="form-label">Nombre</label>
						<input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}">
						@error('name')
						<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-lg-6">
					<div class="mb-3">
						<label class="form-label">Usuario</label>
						<input type="text" class="form-control @error('user') is-invalid @enderror" name="user" value="{{ old('user') }}">
						@error('user')
						<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-lg-6">
					<div class="mb-3">
						<label class="form-label">Contrase&ntilde;a</label>
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
