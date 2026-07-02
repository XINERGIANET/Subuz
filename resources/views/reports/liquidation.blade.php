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
	<div class="card-header d-flex justify-content-between align-items-center">
		<h4 class="mb-0">Generar Reporte de Liquidación</h4>
		<a href="{{ route('reports.liquidations_history') }}" class="btn btn-outline-primary">
			<i class="ti ti-history icon"></i> Ver historial
		</a>
	</div>
	<div class="card-body">
		<form class="mb-3" id="liquidation-form" action="{{ route('reports.pdf') }}">
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
						<label class="form-label">Fecha Inicio</label>
						<input type="date" class="form-control" name="start_date" value="{{ now()->startOfWeek()->format('Y-m-d') }}">
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Fecha Fin</label>
						<input type="date" class="form-control" name="end_date" value="{{ now()->endOfWeek()->format('Y-m-d') }}">
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Fecha de pago</label>
						<input type="date" class="form-control" name="payment_date">
					</div>
				</div>
			</div>

			<button type="button" id="btn-generate-report" class="btn btn-brand"><i class="ti ti-search icon"></i> Generar reporte</button>
		</form>
	</div>
</div>

<div class="modal modal-blur fade" id="correlativeModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Agregar número de comprobante (Opcional)</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label">¿Cómo deseas agregar el comprobante/factura?</label>
					<div class="form-selectgroup form-selectgroup-boxes d-flex flex-column">
						<label class="form-selectgroup-item flex-fill">
							<input type="radio" name="correlative_type_select" value="" class="form-selectgroup-input" checked>
							<div class="form-selectgroup-label d-flex align-items-center p-3">
								<div class="me-3">
									<span class="form-selectgroup-check"></span>
								</div>
								<div>
									<strong>No agregar comprobante</strong>
									<div class="text-muted small">Generar el reporte normalmente</div>
								</div>
							</div>
						</label>
						<label class="form-selectgroup-item flex-fill mt-2">
							<input type="radio" name="correlative_type_select" value="general" class="form-selectgroup-input">
							<div class="form-selectgroup-label d-flex align-items-center p-3">
								<div class="me-3">
									<span class="form-selectgroup-check"></span>
								</div>
								<div>
									<strong>En general (Un solo comprobante para todo)</strong>
									<div class="text-muted small">El comprobante aparecerá en la cabecera del PDF</div>
								</div>
							</div>
						</label>
						<label class="form-selectgroup-item flex-fill mt-2">
							<input type="radio" name="correlative_type_select" value="per_sale" class="form-selectgroup-input">
							<div class="form-selectgroup-label d-flex align-items-center p-3">
								<div class="me-3">
									<span class="form-selectgroup-check"></span>
								</div>
								<div>
									<strong>Por cada venta</strong>
									<div class="text-muted small">Podrás ingresar un comprobante distinto para cada venta</div>
								</div>
							</div>
						</label>
					</div>
				</div>

				<div id="general_correlative_container" class="d-none mb-3">
					<label class="form-label">Número de comprobante general</label>
					<input type="text" class="form-control" id="general_correlative_input" placeholder="Ej: F001-000123">
				</div>

				<div id="per_sale_correlative_container" class="d-none">
					<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
						<table class="table table-sm table-vcenter">
							<thead>
								<tr>
									<th>Guía</th>
									<th>Fecha</th>
									<th>Total</th>
									<th>Comprobante</th>
								</tr>
							</thead>
							<tbody id="sales_correlative_table_body">
							</tbody>
						</table>
					</div>
					<div id="sales_loading" class="text-center py-3 d-none">
						<div class="spinner-border text-primary" role="status"></div>
						<div class="mt-2 text-muted">Cargando ventas...</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-brand" id="btn-generate-with-correlative">Continuar y Generar PDF</button>
			</div>
		</div>
	</div>
</div>
@endsection

@section('scripts')
<script>
	$(document).ready(function(){

		$('#btn-generate-report').on('click', function(e){
			e.preventDefault();
			let client = $('[name="client_id"]').val();
			if(!client){
				ToastError.fire({
					title: 'Error',
					text: 'Debe seleccionar un cliente'
				});
				return false;
			}
			
			// Reset modal state
			$('input[name="correlative_type_select"][value=""]').prop('checked', true).trigger('change');
			$('#general_correlative_input').val('');
			$('#sales_correlative_table_body').empty();
			
			$('#correlativeModal').modal('show');
		});

		$('input[name="correlative_type_select"]').on('change', function(){
			let type = $(this).val();
			$('#general_correlative_container').toggleClass('d-none', type !== 'general');
			$('#per_sale_correlative_container').toggleClass('d-none', type !== 'per_sale');

			if (type === 'per_sale') {
				let client_id = $('[name="client_id"]').val();
				let start_date = $('[name="start_date"]').val();
				let end_date = $('[name="end_date"]').val();
				
				$('#sales_loading').removeClass('d-none');
				$('#sales_correlative_table_body').empty();
				
				$.ajax({
					url: '{{ route('reports.liquidation.sales') }}',
					data: { client_id, start_date, end_date },
					success: function(res) {
						$('#sales_loading').addClass('d-none');
						if(res.status && res.sales.length > 0) {
							let html = '';
							res.sales.forEach(sale => {
								html += `
								<tr>
									<td>${sale.guide}</td>
									<td>${sale.date}</td>
									<td>S/${sale.total}</td>
									<td>
										<input type="text" class="form-control form-control-sm sale-correlative-input" data-id="${sale.id}" placeholder="Opcional">
									</td>
								</tr>
								`;
							});
							$('#sales_correlative_table_body').html(html);
						} else {
							$('#sales_correlative_table_body').html('<tr><td colspan="4" class="text-center text-muted">No se encontraron ventas de crédito para este cliente en las fechas seleccionadas.</td></tr>');
						}
					}
				});
			}
		});

		$('#btn-generate-with-correlative').on('click', function(){
			let type = $('input[name="correlative_type_select"]:checked').val();
			
			$('.dynamic-correlative-inputs').remove();
			
			let form = $('#liquidation-form');
			
			if (type) {
				form.append(`<input type="hidden" name="correlative_type" class="dynamic-correlative-inputs" value="${type}">`);
				
				if (type === 'general') {
					form.append(`<input type="hidden" name="general_correlative" class="dynamic-correlative-inputs" value="${$('#general_correlative_input').val()}">`);
				} else if (type === 'per_sale') {
					$('.sale-correlative-input').each(function(){
						let id = $(this).data('id');
						let val = $(this).val();
						form.append(`<input type="hidden" name="sale_correlatives[${id}]" class="dynamic-correlative-inputs" value="${val}">`);
					});
				}
			}
			
			$('#correlativeModal').modal('hide');
			form[0].submit();
		});

		// Display server-side errors as toasts
		@if(session('error'))
			ToastError.fire({
				title: 'Error',
				text: '{{ session('error') }}'
			});
		@endif

		new TomSelect('.ts-clients', {
			valueField: 'id',
			labelField: 'name',
			searchField: ['name', 'document'],
			copyClassesToDropdown: false,
			dropdownClass: 'dropdown-menu ts-dropdown',
			optionClass:'dropdown-item',
			dropdownParent: 'body',
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