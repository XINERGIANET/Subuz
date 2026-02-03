@extends('template.app')

@section('title', 'Ajustes')

@section('content')
<div class="card mb-4">
	<div class="card-header">
		<h4 class="card-title">Cambiar contraseña</h4>
	</div>
	<div class="card-body">
		<form method="POST" action="{{ route('settings.update') }}">
			@csrf
			<div class="row">
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Contraseña actual</label>
						<input type="password" class="form-control @error('password') is-invalid @enderror" name="password">
						@error('password')
						<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Nueva contraseña</label>
						<input type="password" class="form-control @error('new_password') is-invalid @enderror" name="new_password">
						@error('new_password')
						<div class="invalid-feedback">{{ $message }}</div>
						@enderror
					</div>
				</div>
			</div>
			<button class="btn btn-primary" id="btn-save">
				<i class="ti ti-device-floppy icon"></i> Guardar
			</button>
		</form>
	</div>
</div>
@endsection