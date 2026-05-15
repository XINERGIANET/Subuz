@extends('template.app')

@section('title', 'Cobranza de Pendiente de pago')

@section('content')
<nav class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
    <li class="breadcrumb-item active">Pendiente de pago</li>
  </ol>
</nav>

<div class="row row-cards mb-4">
    <div class="col-md-4">
        <div class="card metric-card border-0 shadow-sm overflow-hidden">
            <div class="card-status-start bg-warning"></div>
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-warning-lt text-warning avatar avatar-md">
                            <i class="ti ti-clock fs-2"></i>
                        </div>
                    </div>
                    <div class="col text-truncate">
                        <div class="text-uppercase text-muted small fw-bold">Total Pendiente</div>
                        <div class="h2 mb-0 fw-bold text-warning">S/{{ number_format($total, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-0 py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <h3 class="card-title fw-bold mb-0"><i class="ti ti-history me-2"></i>Acciones</h3>
        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-end w-100 w-md-auto">
            <a href="{{ route('charges.history', ['type' => 'Contado']) }}" class="btn btn-outline-primary btn-pill px-3 py-2 flex-grow-1 flex-md-grow-0">
                <i class="ti ti-history icon me-1"></i> <span class="d-none d-sm-inline">Historial</span><span class="d-inline d-sm-none">Historial</span>
            </a>
            <a href="{{ route('sales.excel', ['is_pending' => 1] + request()->all()) }}" class="btn btn-success btn-pill px-3 py-2 flex-grow-1 flex-md-grow-0 shadow-sm">
                <i class="ti ti-file-spreadsheet icon me-1"></i> Excel
            </a>
            <a href="{{ route('sales.pdf', ['is_pending' => 1] + request()->all()) }}" class="btn btn-danger btn-pill px-3 py-2 flex-grow-1 flex-md-grow-0 shadow-sm">
                <i class="ti ti-file-type-pdf icon me-1"></i> PDF
            </a>
        </div>
    </div>
	<div class="card-body bg-light-lt py-3">
		<form>
			<div class="row g-3 align-items-end">
				<div class="col-md-3">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Cliente</label>
					<select class="form-select ts-clients" name="client_id">
						<option value="">Seleccionar cliente</option>
						@if(isset($selected_client) && $selected_client)
							<option value="{{ $selected_client->id }}" selected>{{ $selected_client->name }}</option>
						@endif
					</select>
				</div>
                <div class="col-md-3">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Fecha desde</label>
					<input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}">
				</div>
                <div class="col-md-3">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Fecha hasta</label>
					<input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
				</div>
                <div class="col-md-3">
			        <button type="submit" class="btn btn-brand w-100 py-2 fw-bold"><i class="ti ti-filter icon me-1"></i> Filtrar</button>
                </div>
			</div>
		</form>
	</div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fw-bold"><i class="ti ti-hourglass me-2"></i>Ventas por Cobrar</h3>
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
					<th width="40"><input class="form-check-input m-0 align-middle" type="checkbox" id="check-all-sales"></th>
					<th>Guía</th>
					<th class="d-none d-md-table-cell">Fecha</th>
					<th>Cliente</th>
					<th>Pagos</th>
					<th class="text-center">Total</th>
					<th class="text-center">Deuda</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($sales as $sale)
				<tr>
					<td><input class="form-check-input m-0 align-middle sale-checkbox" type="checkbox" value="{{ $sale->id }}" data-debt="{{ $sale->debt }}" data-client-id="{{ $sale->client_id }}" data-type="{{ $sale->type }}"></td>
					<td class="fw-bold">
                        {{ $sale->guide }}
                        <div class="d-block d-md-none text-muted small fw-normal">{{ $sale->date->format('d/m/Y') }}</div>
                    </td>
					<td class="d-none d-md-table-cell">{{ $sale->date->format('d/m/Y') }}</td>
					<td class="small">{{ optional($sale->client)->name ?? 'Consumidor Final' }}</td>
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
					<td class="text-center fw-bold text-muted">S/{{ number_format($sale->total, 2) }}</td>
					<td class="text-center fw-bold text-warning">S/{{ number_format($sale->debt, 2) }}</td>
					<td class="text-end">
						<div class="d-flex gap-2 justify-content-end">
							<button class="btn btn-icon btn-outline-primary btn-show" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Ver Detalle">
								<i class="ti ti-eye icon fs-2"></i>
							</button>
							@if(auth()->user()->hasRole('admin'))
								<button class="btn btn-icon btn-brand btn-payment" data-id="{{ $sale->id }}" data-debt="{{ $sale->debt }}" data-type="{{ $sale->type }}" data-bs-toggle="tooltip" title="Registrar Pago">
									<i class="ti ti-cash icon text-white fs-2"></i>
								</button>
								<button class="btn btn-icon btn-outline-danger btn-delete" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Eliminar Venta">
									<i class="ti ti-trash icon fs-2"></i>
								</button>
							@endif
						</div>
					</td>		
				</tr>
                @empty
				<tr>
					<td colspan="6" align="center" class="py-5 text-muted">
                        <i class="ti ti-mood-smile fs-1 mb-2 d-block"></i>
                        No se han encontrado registros pendientes
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
  		<form id="paymentForm" method="POST" enctype="multipart/form-data">
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
			  <div class="mt-3">
				  <label class="form-label small text-muted mb-1">Foto de Evidencia (Opcional)</label>
				  <input type="file" name="photo" id="payment_photo" class="form-control" accept="image/*" capture="camera">
				  <div id="payment_photo_preview_container" class="mt-2 text-center d-none">
					  <img id="payment_photo_preview" src="#" alt="Vista previa" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
				  </div>
			  </div>
  			</div>
  			<div class="modal-footer d-flex justify-content-between align-items-center">
  				<div>
  					<div class="small text-muted">Total Deuda: <span class="fw-bold" id="total-sale-display">S/0.00</span></div>
  					<div class="small text-muted">Saldo Restante: <span class="fw-bold text-danger" id="remaining-balance-display">S/0.00</span></div>
  				</div>
  				<div>
	  				<input type="hidden" name="type" id="sale_type">
	  				<input type="hidden" name="sale_id" id="sale_id">
                    <div id="sale-ids-container"></div>
	  				<input type="hidden" id="total_sale_value">
					<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
					<button type="submit" class="btn btn-brand" id="btn-submit-payment"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  				</div>
  			</div>
  		</form>
  	</div>
  </div>
</div>

<div class="modal modal-blur fade" id="showModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  		  <h5 class="modal-title">Detalle de venta</h5>
  		  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
  		<div class="modal-body">
  		  <div class="mb-3 d-none" id="photo-container">
  		    <label class="form-label small fw-bold text-muted text-uppercase mb-1">Foto de evidencia</label>
  		    <div class="text-center">
  		      <div class="image-zoom-container">
  		        <img src="" id="show-photo" class="img-fluid" style="max-height: 400px;">
  		      </div>
  		    </div>
  		  </div>
  		  <div class="table-responsive">
			<table class="table table-vcenter">
				<thead class="table-corporate-header">
					<tr>
						<th>Producto</th>
						<th>Precio</th>
						<th>Cantidad</th>
						<th>Subtotal</th>
					</tr>
				</thead>
				<tbody id="tbl-show-items"></tbody>
				<tfoot>
					<tr>
						<td colspan="3" class="text-end fw-bold">Total:</td>
						<td class="fw-bold fs-4 text-brand" id="modal-sale-total">S/0.00</td>
					</tr>
				</tfoot>
			</table>
		  </div>
  		</div>
        <div class="modal-footer bg-light-subtle">
            <button type="button" class="btn btn-brand w-100 py-2" data-bs-dismiss="modal">Aceptar</button>
        </div>
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
			dropdownParent: 'body',
			load: function(query, callback){
				$.ajax({
					url: '{{ route('clients.api') }}?q=' + encodeURIComponent(query),
					method: 'GET',
					success: function(data){
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

	var paymentMethodOptions = `
		<option value="">Seleccionar</option>
		@foreach($payment_methods as $payment_method)
		<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
		@endforeach
	`;

	$(document).on('click', '.btn-payment', function(){
		var sale_id = $(this).data('id');
		var debt = parseFloat($(this).data('debt'));
		var type = $(this).data('type');

		$('#sale_id').val(sale_id);
        $('#sale-ids-container').html('');
		$('#sale_type').val(type);
		$('#total_sale_value').val(debt);
		$('#total-sale-display').text('S/' + debt.toFixed(2));
		
		// Reset file input
		$('#payment_photo').val('');
		$('#payment_photo_preview_container').addClass('d-none');
		$('#payment_photo_preview').attr('src', '#');

		// Reset to one line
		$('#payment-lines').html('');
		addPaymentLine();
		calculateBalance(); 
		$('#paymentModal').modal('show');
	});

    $(document).on('change', '#check-all-sales', function() {
        $('.sale-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
        togglePaySelectedButton();
    });

    $(document).on('change', '.sale-checkbox', function() {
        if ($('.sale-checkbox:checked').length == $('.sale-checkbox:not(:disabled)').length && $('.sale-checkbox:not(:disabled)').length > 0) {
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
        var type = '';
        
        $('.sale-checkbox:checked').each(function() {
            totalDebt += parseFloat($(this).data('debt'));
            saleIdsHtml += '<input type="hidden" name="sale_ids[]" value="' + $(this).val() + '">';
            type = $(this).data('type');
        });

        $('#sale_id').val(''); // Clear single sale id
        $('#sale-ids-container').html(saleIdsHtml); // Set multiple sale ids
        $('#sale_type').val(type);
        
        $('#total_sale_value').val(totalDebt);
        $('#total-sale-display').text('S/' + totalDebt.toFixed(2));
        
        // Reset file input
        $('#payment_photo').val('');
        $('#payment_photo_preview_container').addClass('d-none');
        $('#payment_photo_preview').attr('src', '#');

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
		
		if (remaining <= 0) {
			$display.removeClass('text-danger').addClass('text-success');
		} else {
			$display.removeClass('text-success').addClass('text-danger');
		}
        // Always enable save button to allow partial payments
        $('#btn-submit-payment').prop('disabled', false);
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

	$(document).on('change', '#payment_photo', function() {
		const file = this.files[0];
		if (file) {
			let reader = new FileReader();
			reader.onload = function(event) {
				$('#payment_photo_preview').attr('src', event.target.result);
				$('#payment_photo_preview_container').removeClass('d-none');
			}
			reader.readAsDataURL(file);
		}
	});

	$('#paymentForm').submit(function(e){
		e.preventDefault();
		
		var formData = new FormData(this);

		$.ajax({
			url: '{{ route('payments.store') }}',
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false,
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
                var msg = 'Ocurrió un error en el servidor';
                if(err.responseJSON && err.responseJSON.error) msg = err.responseJSON.error;
				ToastError.fire({ text: msg });
			}
		});

	});


	$(document).on('mousemove', '.image-zoom-container', function(e) {
		const rect = this.getBoundingClientRect();
		const x = e.clientX - rect.left;
		const y = e.clientY - rect.top;
		
		const width = rect.width;
		const height = rect.height;
		
		const xPercent = (x / width) * 100;
		const yPercent = (y / height) * 100;
		
		$(this).find('img').css('transform-origin', `${xPercent}% ${yPercent}%`);
	});

	$(document).on('click', '.btn-delete', function(){
		var id = $(this).data('id');
		var message = '¿Estás seguro que deseas ELIMINAR esta venta? Esta acción borrará permanentemente el registro y sus pagos, y restaurará el stock.';

		if(confirm(message)){
			$.ajax({
				url: '{{ url('sales') }}' + '/' + id,
				method: 'DELETE',
				data: {
					_token: '{{ csrf_token() }}'
				},
				success: function(data){
					if(data.status){
						location.reload();
					}else{
						alert(data.error || 'Ocurrió un error al procesar la solicitud.');
					}
				},
				error: function(err){
					console.log(err);
				}
			});
		}
	});

	$(document).on('click', '.btn-show', function(){
		var id = $(this).data('id');

		$.ajax({
			url: '{{ url('sales') }}' + '/' + id + '/details',
			method: 'GET',
			success: function(data){
				if(data.status){
					var html = '';

					data.details.forEach(function(item){
						var subtotal = (Number(item.price)*Number(item.quantity)).toFixed(2);
						html += `
							<tr>
								<td>${item.product.name}</td>
								<td>S/${Number(item.price).toFixed(2)}</td>
								<td>${item.quantity}</td>
								<td>S/${subtotal}</td>
							</tr>
						`;
					});

					$('#tbl-show-items').html(html);
					$('#modal-sale-total').text('S/' + Number(data.total).toFixed(2));

					if(data.photo && data.photo.indexOf('photo-view/') !== -1 && !data.photo.endsWith('photo-view/')){
						$('#show-photo').attr('src', data.photo);
						$('#photo-container').removeClass('d-none');
					}else{
						$('#photo-container').addClass('d-none');
						$('#show-photo').attr('src', '');
					}

					$('#showModal').modal('show');
				}
			},
			error: function(err){
				console.log(err);
				ToastError.fire({ text: 'Error al cargar los detalles' });
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
                        ToastError.fire({ text: data.error });
                    }
                },
                error: function(err){
                    console.log(err);
                    ToastError.fire({ text: 'Ocurrió un error al intentar restablecer el pago.' });
                }
            });
        }
    });
</script>
@endsection