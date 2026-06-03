@extends('template.app')

@section('title', 'Cobranza de Crédito')

@section('content')
<nav class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Crédito</li>
  </ol>
</nav>

<div class="row row-cards mb-4">
    <div class="col-md-4">
        <div class="card metric-card border-0 shadow-sm overflow-hidden">
            <div class="card-status-start bg-danger"></div>
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-danger-lt text-danger avatar avatar-md">
                            <i class="ti ti-receipt-refund fs-2"></i>
                        </div>
                    </div>
                    <div class="col text-truncate">
                        <div class="text-uppercase text-muted small fw-bold">Total Deuda Crédito</div>
                        <div class="h2 mb-0 fw-bold text-danger">S/{{ number_format($total, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fw-bold"><i class="ti ti-history me-2"></i>Acciones</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('charges.history', ['type' => 'Credito']) }}" class="btn btn-outline-primary btn-pill px-4">
                <i class="ti ti-history icon me-1"></i> Ver historial de créditos
            </a>
            <a href="{{ route('sales.excel', ['is_credit' => 1] + request()->all()) }}" class="btn btn-success btn-pill px-4 shadow-sm">
                <i class="ti ti-file-spreadsheet icon me-1"></i> Excel
            </a>
            <a href="{{ route('sales.pdf', ['is_credit' => 1] + request()->all()) }}" class="btn btn-danger btn-pill px-4 shadow-sm">
                <i class="ti ti-file-type-pdf icon me-1"></i> PDF Detallado
            </a>
            <a href="{{ route('sales.pdf_summary', ['is_credit' => 1] + request()->all()) }}" class="btn btn-warning btn-pill px-4 shadow-sm">
                <i class="ti ti-file-type-pdf icon me-1"></i> PDF Resumen
            </a>
        </div>
    </div>
	<div class="card-body bg-light-lt py-3">
		<form>
			<div class="row g-3 align-items-end">
				<div class="col-md-4">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Cliente</label>
					<select class="form-select ts-clients" name="client_id">
						<option value="">Seleccionar cliente</option>
						@if($client)
						<option value="{{ $client->id }}" selected>{{ $client->name }}</option>
						@endif
					</select>
				</div>
                <div class="col-md-2">
                    <label class="form-label small fw-medium text-muted text-uppercase mb-1">Desde</label>
                    <input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-medium text-muted text-uppercase mb-1">Hasta</label>
                    <input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
                </div>
                <div class="col-md-2">
			        <button type="submit" class="btn btn-brand w-100 py-2 fw-bold"><i class="ti ti-filter icon me-1"></i> Filtrar</button>
                </div>
			</div>
		</form>
	</div>
</div>

<div class=" card border-0 shadow-sm overflow-hidden">
    <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fw-bold"><i class="ti ti-credit-card me-2"></i>Ventas al Crédito</h3>
        @if(auth()->user()->hasRole('admin'))
        <button class="btn btn-brand d-none" id="btn-pay-selected">
            <i class="ti ti-cash icon me-1"></i> Pagar seleccionados
        </button>
        @endif
    </div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th class="w-1"><input class="form-check-input m-0 align-middle" type="checkbox" id="check-all-sales"></th>
					<th>Guía</th>
					<th>Fecha</th>
					<th>Cliente</th>
					<th class="text-center">Estado</th>
					<th>Pagos Realizados</th>
					<th class="text-center">Total</th>
					<th class="text-center">Deuda</th>
					<th class="text-end">Acción</th>
				</tr>
			</thead>
			<tbody>
				@forelse($sales as $sale)
				<tr>
					<td><input class="form-check-input m-0 align-middle sale-checkbox" type="checkbox" value="{{ $sale->id }}" data-debt="{{ $sale->debt }}" data-client-id="{{ $sale->client_id }}"></td>
					<td class="fw-bold">{{ $sale->guide }}</td>
					<td>{{ $sale->date->format('d/m/Y') }}</td>
					<td>{{ optional($sale->client)->name ?? 'N/A' }}</td>
					<td class="text-center">
						@if($sale->paid || $sale->type == 'Pago pendiente' || $sale->movements->where('type', 'debt')->isNotEmpty())
						<span class="badge bg-success-lt px-2 py-1">Entregado</span>
						@else
						<span class="badge bg-warning-lt px-2 py-1">No entregado</span>
						@endif
					</td>
					<td>
						<div class="d-flex flex-wrap gap-1 align-items-center">
							@forelse($sale->payments as $payment)
							<span class="badge bg-blue-lt fw-normal pe-4 position-relative" style="text-transform: none;">
								<span class="fw-bold">S/{{ number_format($payment->amount, 2) }}</span>
								<span class="ms-1 opacity-75">({{ optional($payment->payment_method)->name }})</span>
                                @if(auth()->user()->hasRole('admin'))
                                <a href="javascript:void(0)" class="btn-delete-payment text-danger position-absolute top-50 end-0 translate-middle-y me-1" data-id="{{ $payment->id }}" title="Restablecer pago">
                                    <i class="ti ti-x fs-4"></i>
                                </a>
                                @endif
							</span>
                            @empty
                            <span class="text-muted small italic">Sin pagos</span>
							@endforelse
						</div>
					</td>
					<td class="text-center fw-bold text-dark">S/{{ number_format($sale->total, 2) }}</td>
					<td class="text-center fw-bold text-danger">S/{{ number_format($sale->debt, 2) }}</td>
					<td class="text-end">
						@if(auth()->user()->hasRole('admin'))
						<button class="btn btn-icon btn-brand btn-payment" data-id="{{ $sale->id }}" data-debt="{{ $sale->debt }}" data-bs-toggle="tooltip" title="Registrar Pago">
							<i class="ti ti-cash icon text-white fs-2"></i>
						</button>
						@endif
					</td>		
				</tr>
                @empty
				<tr>
					<td colspan="9" align="center" class="py-5 text-muted">
                        <i class="ti ti-mood-smile fs-1 mb-2 d-block"></i>
                        No se han encontrado resultados de créditos pendientes
                    </td>
				</tr>
				@endforelse
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
                    <div id="sale-ids-container"></div>
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

	$(document).on('click', '.btn-payment', function(){

		var sale_id = $(this).data('id');
		var debt = parseFloat($(this).data('debt'));

		$('#sale_id').val(sale_id);
        $('#sale-ids-container').html('');
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

    $(document).on('change', '#check-all-sales', function() {
        $('.sale-checkbox').prop('checked', $(this).prop('checked'));
        togglePaySelectedButton();
    });

    $(document).on('change', '.sale-checkbox', function() {
        if ($('.sale-checkbox:checked').length == $('.sale-checkbox').length) {
            $('#check-all-sales').prop('checked', true);
        } else {
            $('#check-all-sales').prop('checked', false);
        }
        togglePaySelectedButton();
    });

    function togglePaySelectedButton() {
        var checked = $('.sale-checkbox:checked');
        if (checked.length > 0) {
            var firstClientId = $(checked[0]).data('client-id');
            var differentClient = false;
            
            checked.each(function() {
                if ($(this).data('client-id') !== firstClientId) {
                    differentClient = true;
                    return false;
                }
            });

            if (differentClient) {
                $('#btn-pay-selected').addClass('d-none');
            } else {
                $('#btn-pay-selected').removeClass('d-none');
            }

            // Disable others that don't match the first checked client
            $('.sale-checkbox:not(:checked)').each(function() {
                if ($(this).data('client-id') !== firstClientId) {
                    $(this).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });
        } else {
            $('#btn-pay-selected').addClass('d-none');
            $('.sale-checkbox').prop('disabled', false);
        }
    }

    $(document).on('click', '#btn-pay-selected', function() {
        var totalDebt = 0;
        var saleIdsHtml = '';
        
        $('.sale-checkbox:checked').each(function() {
            totalDebt += parseFloat($(this).data('debt'));
            saleIdsHtml += '<input type="hidden" name="sale_ids[]" value="' + $(this).val() + '">';
        });

        $('#sale_id').val(''); // Clear single sale id
        $('#sale-ids-container').html(saleIdsHtml); // Set multiple sale ids
        
        $('#total_debt_value').val(totalDebt);
        $('#total-debt-display').text('S/' + totalDebt.toFixed(2));
        
        // Reset to one line
        $('#payment-lines').html('');
        addPaymentLine();
        calculateBalance(); // Initial calculate
        $('#paymentModal').modal('show');
    });

	$('#paymentForm').submit(function(e){
		e.preventDefault();

		var $form = $(this);
		var $submit = $form.find('button[type="submit"]');
		if($form.data('submitting')){
			return;
		}

		$form.data('submitting', true);
		$submit.prop('disabled', true);

		$.ajax({
			url: '{{ route('payments.store') }}',
			method: 'POST',
			data: $form.serialize(),
			success: function(data){
				if(data.status){
					$('#paymentModal').modal('hide');
					$('#paymentForm')[0].reset();
					location.reload();
				}else{
					$form.data('submitting', false);
					$submit.prop('disabled', false);
					alert(data.error);
				}
			},
			error: function(err){
				$form.data('submitting', false);
				$submit.prop('disabled', false);
				console.log(err);
			}
		});

	});

    $(document).on('click', '.btn-delete-payment', function(e){
        e.preventDefault();
        e.stopPropagation();
        var payment_id = $(this).data('id');
        if(confirm('¿Está seguro de restablecer este pago? La deuda volverá a su estado anterior.')){
            $.ajax({
                url: '{{ url("payments") }}/' + payment_id,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(data){
                    if(data.status){
                        location.reload();
                    } else {
                        alert(data.error);
                    }
                },
                error: function(err){
                    console.log(err);
                    alert('Ocurrió un error al intentar restablecer el pago.');
                }
            });
        }
    });
</script>
@endsection
