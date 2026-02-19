@extends('template.app')

@section('title', 'Reporte de Liquidación')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Reportes</li>
    <li class="breadcrumb-item active">Liquidación</li>
  </ol>
</nav>
<div class="card">
	<div class="card-body">
		<form class="mb-3" action="{{ route('reports.pdf') }}">
			<div class="row">
				<div class="col-lg-6">
					<div class="mb-3">
						<label class="form-label">Cliente</label>
						<select class="form-select ts-clients" name="client_id">
							<option value="">Seleccionar</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Fecha</label>
						<input type="date" class="form-control" name="date" value="{{ now()->format('Y-m-d') }}">
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Fecha de pago</label>
						<input type="date" class="form-control" name="payment_date">
					</div>
				</div>
			</div>
			<div class="mb-3">
				<label class="form-check form-switch">
					<input class="form-check-input" type="checkbox" name="send_mail" value="1">
					<span class="form-check-label">Enviar a correo</span>
				</label>
			</div>
			<button type="submit" class="btn btn-brand"><i class="ti ti-search icon"></i> Generar reporte</button>
		</form>
	</div>
</div>
@endsection

@section('scripts')
<script>
	$(document).ready(function(){

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
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			}
		});
	});
</script>
@endsection