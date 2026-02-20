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
			<button type="submit" class="btn btn-brand"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>Guía de remisión</th>
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Estado</th>
					<th>Pagos</th>
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
					<td>
						@if($sale->paid || $sale->type == 'Pago pendiente')
						<span class="badge bg-success-lt">Entregado</span>
						@else
						<span class="badge bg-warning-lt">No entregado</span>
						@endif
					</td>
					<td>
						<div class="d-flex flex-column gap-1 align-items-start">
							@foreach($sale->payments as $payment)
							<span class="badge bg-blue-lt fw-normal" style="text-transform: none;">
								<span class="fw-bold">S/{{ number_format($payment->amount, 2) }}</span>
								<span class="ms-1 opacity-75">({{ optional($payment->payment_method)->name }})</span>
							</span>
							@endforeach
						</div>
					</td>
					<td>S/{{ $sale->total }}</td>
					<td>S/{{ $sale->debt }}</td>
					<td>
						<div class="d-flex gap-2">
							@if(auth()->user()->hasRole('admin'))
							<button class="btn btn-icon btn-brand btn-payment" data-id="{{ $sale->id }}" data-debt="{{ $sale->debt }}" data-bs-toggle="tooltip" title="Registrar Pago">
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
  			  <div id="payment-lines">
  			  	<!-- Dynamic content -->
  			  </div>
  			  <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-payment-line">
  			  	<i class="ti ti-plus icon"></i> Agregar método de pago
  			  </button>
  			</div>
  			<div class="modal-footer d-flex justify-content-between align-items-center">
  				<div>
  					<div class="small text-muted">Total Deuda: <span class="fw-bold" id="total-debt-display">S/0.00</span></div>
  					<div class="small text-muted">Saldo Restante: <span class="fw-bold text-danger" id="remaining-balance-display">S/0.00</span></div>
  				</div>
  				<div>
	  				<input type="hidden" name="type" value="Credito">
	  				<input type="hidden" name="sale_id" id="sale_id">
	  				<input type="hidden" id="total_debt_value">
					<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
					<button type="submit" class="btn btn-brand"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  				</div>
  			</div>
  		</form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
	var paymentMethodOptions = `
		<option value="">Seleccionar</option>
		@foreach($payment_methods as $payment_method)
		<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
		@endforeach
	`;

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
		var debt = parseFloat($(this).data('debt'));

		$('#sale_id').val(sale_id);
		$('#total_debt_value').val(debt);
		$('#total-debt-display').text('S/' + debt.toFixed(2));
		
		// Reset to one line
		$('#payment-lines').html('');
		addPaymentLine();
		calculateBalance(); // Initial calculate
		$('#paymentModal').modal('show');
	});

	function addPaymentLine() {
		var index = $('.payment-line').length;
		var html = `
			<div class="row payment-line mb-2">
				<div class="col-lg-6">
					<div class="mb-1">
						<label class="form-label">Forma de pago</label>
						<select class="form-select" name="payments[${index}][payment_method_id]" required>
							${paymentMethodOptions}
						</select>
					</div>
				</div>
				<div class="col-lg-5">
					<div class="mb-1">
						<label class="form-label">Monto</label>
						<input type="number" step="0.01" class="form-control payment-amount" name="payments[${index}][amount]" required>
					</div>
				</div>
				<div class="col-lg-1 d-flex align-items-end mb-1">
					${index > 0 ? '<button type="button" class="btn btn-icon btn-danger remove-line"><i class="ti ti-x icon"></i></button>' : ''}
				</div>
			</div>
		`;
		$('#payment-lines').append(html);
	}
	
	function calculateBalance() {
		var totalDebt = parseFloat($('#total_debt_value').val()) || 0;
		var currentPayment = 0;
		
		$('.payment-amount').each(function() {
			currentPayment += parseFloat($(this).val()) || 0;
		});
		
		var remaining = totalDebt - currentPayment;
		
		// Prevent negative zero or small float errors
		if (Math.abs(remaining) < 0.001) remaining = 0;
		
		var remainingText = 'S/' + remaining.toFixed(2);
		var $display = $('#remaining-balance-display');
		
		$display.text(remainingText);
		
		if (remaining < 0) {
			$display.removeClass('text-danger').addClass('text-success'); // Overpaid? Or warn? Usually warn if negative debt.
			// Let's keep it simple: if negative, it means paying more than debt -> usually bad.
			// But user asked to "subtract balance". 
			// If remaining > 0 (still owe), text-danger is fine (debt exists). 
			// If remaining == 0 (fully paid), maybe text-success.
		} else if (remaining === 0) {
			$display.removeClass('text-danger').addClass('text-success');
		} else {
			$display.removeClass('text-success').addClass('text-danger');
		}
	}

	$(document).on('click', '#add-payment-line', function() {
		addPaymentLine();
	});

	$(document).on('click', '.remove-line', function() {
		$(this).closest('.payment-line').remove();
		calculateBalance();
	});
	
	$(document).on('input', '.payment-amount', function() {
		calculateBalance();
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