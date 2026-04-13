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
	<div class="card-header d-flex flex-column flex-md-row justify-content-between gap-3">
		<form method="GET" action="{{ route('users.dispatchers.index') }}" class="flex-grow-1">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Desde</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->toDateString()) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Hasta</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter icon"></i> Filtrar
                    </button>
                    <a href="{{ route('users.dispatchers.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-refresh icon"></i>
                    </a>
                </div>
            </div>
        </form>
		<div class="align-self-end align-self-md-center">
            <a class="btn btn-brand" href="{{ route('users.dispatchers.create') }}">
                <i class="ti ti-plus icon"></i> Crear nuevo
            </a>
        </div>
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
							<a class="btn btn-icon btn-outline-info" href="{{ route('users.dispatchers.report', array_merge(['dispatcher' => $dispatcher->id], request()->query())) }}" data-bs-toggle="tooltip" title="Descargar Reporte PDF">
								<i class="ti ti-file-text icon"></i>
							</a>
							<a class="btn btn-icon btn-edit-corporate" href="{{ route('users.dispatchers.edit', $dispatcher) }}" data-bs-toggle="tooltip" title="Editar">
								<i class="ti ti-edit icon"></i>
							</a>
							<form method="POST" action="{{ route('users.dispatchers.destroy', $dispatcher) }}" onsubmit="return confirm('Eliminar usuario?');">
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
	@if($dispatchers->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $dispatchers->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection
