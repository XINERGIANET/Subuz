@extends('template.app')

@section('title', 'Asistentes')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Asistentes</li>
  </ol>
</nav>

<div class="card">
	<div class="card-header d-flex justify-content-between">
		<div></div>
		<a class="btn btn-brand" href="{{ route('users.assistants.create') }}">
			<i class="ti ti-plus icon"></i> Crear nuevo
		</a>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>#</th>
					<th>Nombre</th>
					<th>Usuario</th>
					<th>Accion</th>
				</tr>
			</thead>
			<tbody>
				@if($assistants->count() > 0)
				@foreach($assistants as $assistant)
				<tr>
					<td>{{ $loop->iteration }}</td>
					<td>{{ $assistant->name }}</td>
					<td>{{ $assistant->user }}</td>
					<td>
						<div class="d-flex gap-2">
							<a class="btn btn-icon btn-edit-corporate" href="{{ route('users.assistants.edit', $assistant) }}" data-bs-toggle="tooltip" title="Editar">
								<i class="ti ti-edit icon"></i>
							</a>
							<form method="POST" action="{{ route('users.assistants.destroy', $assistant) }}" onsubmit="return confirm('Eliminar usuario?');">
								@csrf
								@method('DELETE')
								<button class="btn btn-icon btn-delete-corporate" type="submit" data-bs-toggle="tooltip" title="Eliminar">
									<i class="ti ti-trash icon"></i>
								</button>
							</form>
						</div>
					</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="4" align="center">No se han encontrado registros</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($assistants->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $assistants->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection
