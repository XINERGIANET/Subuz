@extends('template.app')

@section('title', 'Historial')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Historial</li>
  </ol>
</nav>
<div class="card">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			<a class="btn btn-success" href="{{ route('payments.excel') }}">
				<i class="ti ti-download icon"></i> Excel
			</a>
		</div>
		<div class="text-center">
			<span class="d-block small">
				Tienes un total de
			</span>
			<span class="fs-2 fw-bold text-primary">
					S/{{ number_format($total, 2) }}
				</span>
		</div>
	</div>
	<div class="card-body border-bottom">
		<form class="mb-3">
			<div class="row">
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Cliente</label>
						<select class="form-select ts-clients" name="client_id">
							<option value="">Seleccionar</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Fecha desde</label>
						<input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}">
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Fecha hasta</label>
						<input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Tipo de venta</label>
						<select class="form-select" name="type">
							<option value="">Seleccionar</option>
							<option value="Contado" {{ request()->type == 'Contado' ? 'selected' : '' }}>Contado</option>
							<option value="Credito" {{ request()->type == 'Credito' ? 'selected' : '' }}>Crédito</option>
						</select>
					</div>
				</div>
			</div>
			<button type="submit" class="btn btn-brand"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>#</th>
					<th>Cliente</th>
					<th>Monto</th>
					<th>Forma de pago</th>
					<th>Fecha</th>
				</tr>
			</thead>
			<tbody>
				@if($payments->count() > 0)
				@foreach($payments as $payment)
				<tr>
					<td>{{ $loop->iteration }}</td>
					<td>{{ optional(optional($payment->sale)->client)->name }}</td>
					<td>{{ $payment->amount }}</td>
					<td>{{ optional($payment->payment_method)->name }}</td>
					<td>{{ $payment->date->format('d/m/Y') }}</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="5" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($payments->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $payments->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection

@section('scripts')
<script>
	$(document).ready(function () {

		new TomSelect('.ts-clients', {
			valueField: 'id',
			labelField: 'name',
			searchField: ['name', 'document'],
			copyClassesToDropdown: false,
			dropdownClass: 'dropdown-menu ts-dropdown',
			optionClass:'dropdown-item',
			load: function(query, callback){
				$.ajax({
					url: '{{ route('clients.api') }}?q=' + encodeURIComponent(query),
					method: 'GET',
					success: function(data){
						console.log(data);
						callback(data.items);
					},
					error: function(err){
						console.log(err);
					}
				})
			},
			render: {
				option: function(data, escape) {
					return `<div>${escape(data.name)} - ${escape(data.document)}</div>`;
				},
				item: function(data, escape) {
					return `<div>${escape(data.name)} - ${escape(data.document)}</div>`;
				},
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			}
		});
	});
</script>
@endsection