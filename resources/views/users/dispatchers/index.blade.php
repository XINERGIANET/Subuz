@extends('template.app')

@section('title', 'Despachadores')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Despachadores</li>
  </ol>
</nav>

<div class="card">
	<div class="card-header d-flex justify-content-between">
		<div></div>
		<a class="btn btn-brand" href="{{ route('users.dispatchers.create') }}">
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
				@if($dispatchers->count() > 0)
				@foreach($dispatchers as $dispatcher)
				<tr>
					<td>{{ $loop->iteration }}</td>
					<td>{{ $dispatcher->name }}</td>
					<td>{{ $dispatcher->user }}</td>
					<td>
						<div class="d-flex gap-2">
							<a class="btn btn-icon btn-edit-corporate" href="{{ route('users.dispatchers.edit', $dispatcher) }}">
								<i class="ti ti-edit icon"></i>
							</a>
							<form method="POST" action="{{ route('users.dispatchers.destroy', $dispatcher) }}" onsubmit="return confirm('Eliminar usuario?');">
								@csrf
								@method('DELETE')
								<button class="btn btn-icon btn-delete-corporate" type="submit">
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
	@if($dispatchers->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $dispatchers->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection
