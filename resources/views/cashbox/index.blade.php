@extends('template.app')

@section('title', 'Caja')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Caja</li>
  </ol>
</nav>

<div class="card mb-3">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			@if($cashbox)
			<span class="badge bg-success">Caja abierta</span>
			@else
			<span class="badge bg-danger">Caja cerrada</span>
			@endif
		</div>
		<div>
			@if(!$cashbox)
			<button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#openModal">
				<i class="ti ti-door-enter icon"></i> Aperturar caja
			</button>
			@else
			<button class="btn btn-delete-corporate" data-bs-toggle="modal" data-bs-target="#closeModal">
				<i class="ti ti-door-exit icon"></i> Cerrar caja
			</button>
			@endif
		</div>
	</div>
	<div class="card-body">
		@if($cashbox)
		<div class="row">
			<div class="col-md-3 mb-2">
				<span class="text-muted d-block">Apertura</span>
				<span class="fw-bold">{{ $cashbox->opened_at->format('d/m/Y H:i') }}</span>
			</div>
			<div class="col-md-3 mb-2">
				<span class="text-muted d-block">Monto inicial</span>
				<span class="fw-bold">S/{{ number_format($cashbox->opening_amount, 2) }}</span>
			</div>
			<div class="col-md-3 mb-2">
				<span class="text-muted d-block">Total pagado</span>
				<span class="fw-bold text-success">S/{{ number_format($total_paid, 2) }}</span>
			</div>
			<div class="col-md-3 mb-2">
				<span class="text-muted d-block">Total deuda</span>
				<span class="fw-bold text-danger">S/{{ number_format($total_debt, 2) }}</span>
			</div>
			<div class="col-md-3 mb-2">
				<span class="text-muted d-block">Total gastos</span>
				<span class="fw-bold text-warning">S/{{ number_format($total_expenses, 2) }}</span>
			</div>
		</div>
		@else
		<p class="mb-0">No hay una caja abierta.</p>
		@endif
	</div>
</div>

@if($cashbox)
<div class="card">
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>Fecha</th>
					<th>Venta</th>
					<th>Cliente</th>
					<th>Tipo</th>
					<th>Metodo</th>
					<th>Monto</th>
					<th>Usuario</th>
				</tr>
			</thead>
			<tbody>
				@if($movements->count() > 0)
				@foreach($movements as $movement)
				<tr>
					<td>{{ $movement->date->format('d/m/Y H:i') }}</td>
					<td>{{ optional($movement->sale)->guide ?? 'N/A' }}</td>
					<td>{{ optional(optional($movement->sale)->client)->name ?? 'N/A' }}</td>
					<td>
						@if($movement->type == 'paid')
						<span class="badge bg-success">Pagado</span>
						@else
						<span class="badge bg-warning">Deuda</span>
						@endif
					</td>
					<td>{{ $movement->payment_method ? $movement->payment_method->name : 'N/A' }}</td>
					<td>S/{{ number_format($movement->amount, 2) }}</td>
					<td>{{ $movement->user ? $movement->user->name : 'N/A' }}</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="7" align="center">No hay movimientos registrados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
</div>
@endif

<div class="modal modal-blur fade" id="openModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form method="POST" action="{{ route('cashbox.open') }}">
  			@csrf
  			<div class="modal-header">
  			  <h5 class="modal-title">Aperturar caja</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  				<div class="mb-3">
  					<label class="form-label">Monto inicial</label>
  					<input type="number" step="0.01" class="form-control" name="opening_amount">
  				</div>
  			</div>
  			<div class="modal-footer">
  				<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  				<button type="submit" class="btn btn-brand"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="closeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form method="POST" action="{{ route('cashbox.close') }}">
  			@csrf
  			<div class="modal-header">
  			  <h5 class="modal-title">Cerrar caja</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  				<div class="mb-3">
  					<label class="form-label">Monto de cierre</label>
  					<input type="number" step="0.01" class="form-control" name="closing_amount" value="{{ $suggested_closing_amount }}">
  					<small class="text-muted">Se calcula: apertura + ventas - gastos</small>
  				</div>
  				<div class="mb-3">
  					<label class="form-label">Observacion</label>
  					<textarea class="form-control" name="note" rows="3"></textarea>
  				</div>
  			</div>
  			<div class="modal-footer">
  				<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  				<button type="submit" class="btn btn-delete-corporate"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
    </div>
  </div>
</div>
@endsection
