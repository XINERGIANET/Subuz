@extends('template.app')

@section('title', 'Cobranza de Crédito')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Crédito</li>
  </ol>
</nav>
<div class="card mb-4">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			
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
		<form>
			<div class="row">
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Cliente</label>
						<select class="form-select ts-clients" name="client_id">
							<option value="">Seleccionar</option>
							@if($client)
							<option value="{{ $client->id }}" selected>{{ $client->name }}</option>
							@endif
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
			</div>
			<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>Guía de remisión</th>
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Distrito</th>
					<th>Total</th>
					<th>Deuda</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($sales->count() > 0)
				@foreach($sales as $sale)
				<tr>
					<td>{{ $sale->guide }}</td>
					<td>{{ $sale->date->format('d/m/Y') }}</td>
					<td>{{ optional($sale->client)->name }}</td>
					<td>{{ optional($sale->client)->district }}</td>
					<td>S/{{ $sale->total }}</td>
					<td>S/{{ $sale->debt }}</td>
					<td>
						<div class="d-flex gap-2">
							@if(auth()->user()->hasRole('admin'))
							<button class="btn btn-icon btn-payment" data-id="{{ $sale->id }}">
								<i class="ti ti-cash icon"></i>
							</button>
							@endif
						</div>
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="10" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($sales->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $sales->withQueryString()->links() }}
	</div>
	@endif
</div>



<div class="modal modal-blur fade" id="paymentModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="paymentForm" method="POST">
  			<div class="modal-header">
  			  <h5 class="modal-title">Pagar</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  			  <div class="row">
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label">Forma de pago</label>
  			  			<select class="form-select" name="payment_method_id">
  			  				<option value="">Seleccionar</option>
  			  				@foreach($payment_methods as $payment_method)
  			  				<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
  			  				@endforeach
  			  			</select>
  			  		</div>
  			  	</div>
  			  	<div class="col-lg-6">
  			  		<div class="mb-3">
  			  			<label class="form-label">Monto</label>
  			  			<input type="text" class="form-control" name="amount">
  			  		</div>
  			  	</div>
  			  </div>
  			</div>
  			<div class="modal-footer">
  				<input type="hidden" name="type" value="Credito">
  				<input type="hidden" name="sale_id" id="sale_id">
				<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
				<button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
    </div>
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

	$(document).on('click', '.btn-payment', function(){

		var sale_id = $(this).data('id');

		$('#sale_id').val(sale_id);
		$('#paymentModal').modal('show');
	});

	$('#paymentForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('payments.store') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#paymentModal').modal('hide');
					$('#paymentForm')[0].reset();
					location.reload();
				}else{
					alert(data.error);
				}
			},
			error: function(err){
				console.log(err);
			}
		});

	});
</script>
@endsection