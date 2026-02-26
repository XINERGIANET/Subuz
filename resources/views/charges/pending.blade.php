@extends('template.app')

@section('title', 'Cobranza de Pendiente de pago')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Pendiente de pago</li>
  </ol>
</nav>
<div class="card">
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
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>#</th>
					<th>Guía de remisión</th>
					<th>Fecha</th>
					<th>Cliente</th>
					<th>Total</th>
					<th>Pagar</th>
				</tr>
			</thead>
			<tbody>
				@if($sales->count() > 0)
				@foreach($sales as $sale)
				<tr>
					<td>{{ $loop->iteration }}</td>
					<td>{{ $sale->guide }}</td>
					<td>{{ $sale->date->format('d/m/Y') }}</td>
					<td>{{ optional($sale->client)->name }}</td>
					<td>{{ $sale->total }}</td>
					<td>
						@if(auth()->user()->hasRole('admin'))
						<div class="d-flex gap-2">
							<button class="btn btn-icon btn-brand btn-payment" data-id="{{ $sale->id }}" data-total="{{ $sale->total }}" data-type="{{ $sale->type }}" data-bs-toggle="tooltip" title="Registrar Pago">
								<i class="ti ti-cash icon"></i>
							</button>
						</div>
						@endif
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="6" align="center">No se han encontrado registros</td>
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
  					<div class="small text-muted">Total Venta: <span class="fw-bold" id="total-sale-display">S/0.00</span></div>
  					<div class="small text-muted">Saldo Restante: <span class="fw-bold text-danger" id="remaining-balance-display">S/0.00</span></div>
  				</div>
  				<div>
	  				<input type="hidden" name="type" id="sale_type">
	  				<input type="hidden" name="sale_id" id="sale_id">
	  				<input type="hidden" id="total_sale_value">
					<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
					<button type="submit" class="btn btn-brand" id="btn-submit-payment"><i class="ti ti-device-floppy icon"></i> Guardar</button>
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

	$(document).on('click', '.btn-payment', function(){
		var sale_id = $(this).data('id');
		var total = parseFloat($(this).data('total'));
		var type = $(this).data('type');

		$('#sale_id').val(sale_id);
		$('#sale_type').val(type);
		$('#total_sale_value').val(total);
		$('#total-sale-display').text('S/' + total.toFixed(2));
		
		// Reset to one line
		$('#payment-lines').html('');
		addPaymentLine();
		calculateBalance(); 
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
		var totalSale = parseFloat($('#total_sale_value').val()) || 0;
		var currentPayment = 0;
		
		$('.payment-amount').each(function() {
			currentPayment += parseFloat($(this).val()) || 0;
		});
		
		var remaining = totalSale - currentPayment;
		
		if (Math.abs(remaining) < 0.001) remaining = 0;
		
		var remainingText = 'S/' + remaining.toFixed(2);
		var $display = $('#remaining-balance-display');
		
		$display.text(remainingText);
		
		if (remaining === 0) {
			$display.removeClass('text-danger').addClass('text-success');
			$('#btn-submit-payment').prop('disabled', false);
		} else {
			$display.removeClass('text-success').addClass('text-danger');
			// For Pending Payments, we usually want EXACT payment to close it
			// However, legacy logic might differ. User said "following same logic as others".
			// In sales/dispatch modal, we require exact match. 
			// I'll disable Guardar if remaining != 0 to ensure full liquidation.
			$('#btn-submit-payment').prop('disabled', true);
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
					ToastMessage.fire({ text: 'Pago registrado correctamente' }).then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error });
				}
			},
			error: function(err){
				console.log(err);
				ToastError.fire({ text: 'Ocurrió un error en el servidor' });
			}
		});

	});
</script>
@endsection