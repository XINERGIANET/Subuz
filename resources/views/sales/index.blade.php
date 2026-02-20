@extends('template.app')

@section('title', 'Ventas')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Ventas</li>
  </ol>
</nav>
<div class="card">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
			<a class="btn btn-brand" href="{{ route('sales.create') }}">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</a>
			<a class="btn btn-success" href="{{ route('sales.excel') }}">
				<i class="ti ti-download icon"></i> Excel
			</a>
			@endif
			@if(auth()->user()->hasRole('admin'))
			<div class="mt-2">
				@if($cashbox)
				<span class="badge bg-success">Caja abierta</span>
				@else
				<span class="badge bg-danger">Caja cerrada</span>
				@endif
				<a class="btn btn-outline-secondary btn-sm" href="{{ route('cashbox.index') }}">Ir a caja</a>
			</div>
			@endif
		</div>
		<div class="text-center">
			<span class="d-block small">
				Tienes un total de
			</span>
			<span class="fs-2 fw-bold text-primary">
					S/{{ number_format($total_sales, 2) }}
				</span>
		</div>
	</div>
	@if(!auth()->user()->hasRole('despachador'))
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
						<label class="form-label">Tipo de venta</label>
						<select class="form-select" name="type">
							<option value="">Seleccionar</option>
							<option value="Contado" {{ request()->type == 'Contado' ? 'selected' : '' }}>Contado</option>
							<option value="Credito" {{ request()->type == 'Credito' ? 'selected' : '' }}>Crédito</option>
							<option value="Pago pendiente" {{ request()->type == 'Pago pendiente' ? 'selected' : '' }}>Pago pendiente</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Fecha desde</label>
						<input type="date" class="form-control" name="start_date" value="{{ request()->start_date ? request()->start_date : now()->format('Y-m-d') }}">
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Fecha hasta</label>
						<input type="date" class="form-control" name="end_date" value="{{ request()->end_date ? request()->end_date : now()->format('Y-m-d') }}">
					</div>
				</div>
			</div>
			<button type="submit" class="btn btn-brand"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
	</div>
	@endif
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>#</th>
					<th>Guía de remisión</th>
					<th>Fecha</th>
					<th>Tipo de venta</th>
					<th>Método de pago</th>
					<th>Cliente</th>
					<th>Estado</th>
					<th>Total</th>
					<th>Pagado</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($sales->count() > 0)
				@foreach($sales as $sale)
				<tr>
					<td>{{ $loop->iteration }}</td>
					<td>{{ $sale->guide }}</td>
					<td>{{ $sale->date->format('d/m/Y') }}</td>
					<td>{{ $sale->type }}</td>
					<td>{{ $sale->payment_method ? optional($sale->payment_method)->name : 'N/A' }}</td>
					<td>{{ optional($sale->client)->name }}</td>
					<td>
						@php
							$isDelivered = $sale->paid || $sale->type == 'Pago pendiente' || $sale->movements->where('type', 'debt')->isNotEmpty();
						@endphp
						@if(auth()->user()->hasRole('despachador') && ($sale->type == 'Credito' || $sale->type == 'Contado' || $sale->type == 'Pago pendiente') && !$sale->paid)
							<select class="form-select form-select-sm select-delivery-status" data-id="{{ $sale->id }}">
								<option value="0" {{ !$isDelivered ? 'selected' : '' }}>No entregado</option>
								<option value="1" {{ $isDelivered ? 'selected' : '' }}>Entregado</option>
							</select>
						@else
							@if($isDelivered)
							<span class="badge bg-success-lt">Entregado</span>
							@else
							<span class="badge bg-warning-lt">No entregado</span>
							@endif
						@endif
					</td>
					<td>S/{{ $sale->total }}</td>
					<td>
						@if($sale->paid)
						<span class="badge bg-success"><i class="ti ti-check"></i></span>
						@else
						<span class="badge bg-danger"><i class="ti ti-x"></i></span>
						@endif
					</td>
					<td>
						<div class="d-flex gap-2">
							<button class="btn btn-icon btn-show" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Imprimir">
								<i class="ti ti-printer icon"></i>
							</button>
							@if(auth()->user()->hasRole('despachador') && !$sale->paid && $sale->type != 'Credito' && $sale->type != 'Pago pendiente')
							<button class="btn btn-icon btn-dispatch" data-id="{{ $sale->id }}" data-guide="{{ $sale->guide }}" data-total="{{ $sale->total }}" data-bs-toggle="tooltip" title="Despachar">
								<i class="ti ti-check icon"></i>
							</button>
							@endif
							@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
							<button class="btn btn-icon btn-edit-corporate btn-edit" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Editar">
								<i class="ti ti-edit icon"></i>
							</button>
							<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Eliminar">
								<i class="ti ti-trash icon"></i>
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

<div class="modal modal-blur fade" id="showModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  		  <h5 class="modal-title">Detalle de venta</h5>
  		  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
  		<div class="modal-body">
  		  <table class="table">
  		  	<thead class="table-corporate-header">
  		  		<tr>
  		  			<th>Producto</th>
  		  			<th>Precio</th>
  		  			<th>Cantidad</th>
  		  			<th>Subtotal</th>
  		  		</tr>
  		  	</thead>
  		  	<tbody id="tbl-show-items"></tbody>
  		  </table>
  		</div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
  	<div class="modal-content">
  		<div class="modal-header">
  		  <h5 class="modal-title">Editar venta</h5>
  		  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
  		<div class="modal-body">
  			<div class="col-md-6 mb-4">
  				<label class="form-label">Fecha</label>
  				<input type="date" class="form-control" id="date">
  			</div>
  		  <table class="table mb-4">
  		  	<thead class="table-corporate-header">
  		  		<tr>
  		  			<th>Producto</th>
  		  			<th>Precio</th>
  		  			<th>Cantidad</th>
  		  			<th>Subtotal</th>
  		  		</tr>
  		  	</thead>
  		  	<tbody id="tbl-edit-items"></tbody>
  		  </table>
  		  <input type="hidden" id="sale_id">
  		  <button class="btn btn-brand" id="btn-save">Guardar</button>
  		</div>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="dispatchModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="dispatchForm" method="POST">
  			<div class="modal-header">
  			  <h5 class="modal-title">Confirmar pago</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  				<div class="mb-3 p-3 bg-light rounded border">
  					<div class="small text-uppercase fw-bold text-muted mb-1">Detalle de Venta</div>
  					<div class="h3 mb-0">
  						Venta: <span id="dispatch_guide" class="text-primary"></span> | Total: <span class="text-dark">S/<span id="dispatch_total"></span></span>
  					</div>
  				</div>
  				<div class="mb-3">
  					<label class="form-label fw-bold">¿Se registró el pago?</label>
  					<div class="btn-group w-100" role="group">
  						<input type="radio" class="btn-check" name="paid" id="dispatch_paid_yes" value="1">
  						<label class="btn btn-outline-success py-2 d-flex align-items-center justify-content-center gap-2" for="dispatch_paid_yes">
  							<i class="ti ti-check fs-2"></i> Si, pagado
  						</label>
  						<input type="radio" class="btn-check" name="paid" id="dispatch_paid_no" value="0">
  						<label class="btn btn-outline-danger py-2 d-flex align-items-center justify-content-center gap-2" for="dispatch_paid_no">
  							<i class="ti ti-clock fs-2"></i> Pendiente
  						</label>
  					</div>
  				</div>

  				<div id="dispatchPaymentContainer" style="display:none">
  					<div class="d-flex justify-content-between align-items-center mb-2">
  						<label class="form-label mb-0 fw-bold text-uppercase small">Métodos de Pago</label>
  						<button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-payment">
  							<i class="ti ti-plus me-1"></i> Agregar otro
  						</button>
  					</div>
  					
  					<div id="payment-rows-container">
  						<!-- Payment rows will be injected here -->
  					</div>

  					<div class="mt-3 p-2 rounded bg-primary-lt border border-primary d-flex justify-content-between align-items-center">
  						<span class="fw-bold">Total Distribuido:</span>
  						<span class="h4 mb-0 fw-extrabold" id="total-distributed">S/0.00</span>
  					</div>
  					<div id="payment-warning" class="mt-2 small text-danger fw-bold" style="display:none">
  						<i class="ti ti-alert-triangle me-1"></i> La suma de los montos no coincide con el total.
  					</div>
  				</div>
  			</div>
  			<div class="modal-footer bg-light-subtle">
  				<input type="hidden" name="sale_id" id="dispatch_sale_id">
  				<button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Cancelar</button>
  				<button type="submit" class="btn btn-brand px-4 h3" id="btn-confirm-dispatch">
  					<i class="ti ti-device-floppy me-2"></i> Confirmar Despacho
  				</button>
  			</div>
  		</form>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
	$(document).ready(function () {
		if($('.ts-clients').length){
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
					return `<div>${escape(data.name)}</div>`;
				},
				item: function(data, escape) {
					return `<div>${escape(data.name)}</div>`;
				},
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			}
			});
		}
	});

	$(document).on('click', '.btn-show', function(){

		var id = $(this).data('id');

		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + id + '/details',
			method: 'GET',
			success: function(data){
				if(data.status){
					var html = '';

					data.details.forEach(function(item){
						var subtotal = (Number(item.price)*Number(item.quantity)).toFixed(2);
						html += `
							<tr>
								<td>${item.product.name}</td>
								<td>${item.price}</td>
								<td>${item.quantity}</td>
								<td>${subtotal}</td>
							</tr>
						`;
					});

					$('#tbl-show-items').html(html);

					$('#showModal').modal('show');
				}
			},
			error: function(err){
				console.log(err);
			}
		});

	});

	$(document).on('click', '.btn-edit', function(){

		$('#date').val('');
		$('#tbl-edit-items').html('');

		var id = $(this).data('id');

		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + id + '/edit',
			method: 'GET',
			success: function(data){
				if(data.status){
					var html = '';

					$('#sale_id').val(data.id);
					$('#date').val(data.date);

					data.details.forEach(function(item){
						var subtotal = (Number(item.price)*Number(item.quantity)).toFixed(2);
						html += `
							<tr>
								<td><input type="hidden" name="detail_id[]" value="${item.id}"> ${item.product.name}</td>
								<td><input class="form-control form-control-sm" name="price[]" value="${item.price}" style="width: 100px"></td>
								<td><input class="form-control form-control-sm" name="quantity[]" value="${item.quantity}" style="width: 100px"></td>
								<td>${subtotal}</td>
							</tr>
						`;
					});

					$('#tbl-edit-items').html(html);

					$('#editModal').modal('show');
				}
			},
			error: function(err){
				console.log(err);
			}
		});

	});

	$('#btn-save').click(function(){

		var id = $('#sale_id').val();

		var data = {
			date: null,
			details: {
				id: [],
				price: [],
				quantity: []
			}
		};

		data.date = $('#date').val();

		$('input[name="detail_id[]"]').each(function(){
			data.details.id.push($(this).val());
		});

		$('input[name="price[]"]').each(function(){
			data.details.price.push($(this).val());
		});

		$('input[name="quantity[]"]').each(function(){
			data.details.quantity.push($(this).val());
		});

		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + id,
			method: 'PUT',
			data: data,
			success: function(data){
				if(data.status){
					location.reload();
				}else{
					alert(data.error)
				}
			}
		});
		

	});

	$(document).on('click', '.btn-delete', function(){

		var id = $(this).data('id');

		if(confirm('Â¿EstÃ¡s seguro que deseas borrar el registro?')){

			$.ajax({
				url: '{{ route('sales.index') }}' + '/' + id,
				method: 'DELETE',
				success: function(data){
					if(data.status){
						location.reload();
					}else{
						alert('El registro no se pudo eliminar por que tiene pagos relacionados.')
					}
				},
				error: function(err){
					console.log(err);
				}
			});

		}

	});

	var paymentMethodsHtml = `
		@foreach($payment_methods as $pm)
			<option value="{{ $pm->id }}">{{ $pm->name }}</option>
		@endforeach
	`;

	$(document).on('click', '.btn-dispatch', function(){
		var id = $(this).data('id');
		var guide = $(this).data('guide');
		var total = $(this).data('total');

		$('#dispatch_sale_id').val(id);
		$('#dispatch_guide').text(guide);
		$('#dispatch_total').text(total);
		$('#dispatchForm')[0].reset();
		$('#dispatchPaymentContainer').hide();
		$('#payment-rows-container').empty();
		$('#total-distributed').text('S/0.00');
		$('#payment-warning').hide();

		$('#dispatchModal').modal('show');
	});

	function addPaymentRow(amount = '') {
		var rowCount = $('#payment-rows-container .payment-row').length;
		var html = `
			<div class="payment-row mb-2 border-bottom pb-2">
				<div class="row g-2 align-items-center">
					<div class="col-7">
						<select class="form-select" name="payments[${rowCount}][method_id]" required>
							<option value="">Seleccionar cuenta</option>
							${paymentMethodsHtml}
						</select>
					</div>
					<div class="col-4">
						<div class="input-group input-group-flat">
							<span class="input-group-text ps-2 pe-0">S/</span>
							<input type="number" step="0.01" class="form-control ps-1 txt-payment-amount" name="payments[${rowCount}][amount]" value="${amount}" placeholder="0.00" required>
						</div>
					</div>
					<div class="col-1 text-end">
						${rowCount > 0 ? '<button type="button" class="btn btn-ghost-danger btn-icon btn-sm btn-remove-payment"><i class="ti ti-trash fs-3"></i></button>' : ''}
					</div>
				</div>
			</div>
		`;
		$('#payment-rows-container').append(html);
		calculateDistributed();
	}

	$(document).on('click', '#btn-add-payment', function() {
		var total = parseFloat($('#dispatch_total').text());
		var distributed = 0;
		$('.txt-payment-amount').each(function() {
			distributed += parseFloat($(this).val()) || 0;
		});
		var remaining = (total - distributed).toFixed(2);
		addPaymentRow(remaining > 0 ? remaining : '');
	});

	$(document).on('click', '.btn-remove-payment', function() {
		$(this).closest('.payment-row').remove();
		calculateDistributed();
	});

	$(document).on('input', '.txt-payment-amount', function() {
		calculateDistributed();
	});

	function calculateDistributed() {
		var total = parseFloat($('#dispatch_total').text());
		var distributed = 0;
		$('.txt-payment-amount').each(function() {
			distributed += parseFloat($(this).val()) || 0;
		});
		
		$('#total-distributed').text('S/' + distributed.toFixed(2));
		
		if (Math.abs(distributed - total) > 0.01) {
			$('#total-distributed').addClass('text-danger').removeClass('text-success');
			var diff = (total - distributed).toFixed(2);
			var message = diff > 0 
				? `<i class="ti ti-alert-triangle me-1"></i> Faltan S/${diff} para completar el total.`
				: `<i class="ti ti-alert-triangle me-1"></i> El monto excede el total por S/${Math.abs(diff).toFixed(2)}.`;
			$('#payment-warning').html(message).show();
		} else {
			$('#total-distributed').addClass('text-success').removeClass('text-danger');
			$('#payment-warning').hide();
		}
	}

	$(document).on('change', 'input[name="paid"]', function(){
		if($(this).val() == '1'){
			$('#dispatchPaymentContainer').fadeIn();
			if($('#payment-rows-container').is(':empty')) {
				addPaymentRow($('#dispatch_total').text());
			}
		}else{
			$('#dispatchPaymentContainer').fadeOut();
		}
	});

	$('#dispatchForm').submit(function(e){
		e.preventDefault();
		
		var isPaid = $('input[name="paid"]:checked').val() == '1';
		if(isPaid) {
			var total = parseFloat($('#dispatch_total').text());
			var distributed = 0;
			$('.txt-payment-amount').each(function() {
				distributed += parseFloat($(this).val()) || 0;
			});

			if(Math.abs(distributed - total) > 0.01) {
				ToastError.fire({ text: 'El total distribuido debe coincidir con el total de la venta.' });
				return;
			}
		}

		var id = $('#dispatch_sale_id').val();

		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + id + '/dispatch',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#dispatchModal').modal('hide');
					location.reload();
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(xhr){
				var error = 'Ocurrió un error';
				if(xhr.responseJSON && xhr.responseJSON.error) error = xhr.responseJSON.error;
				ToastError.fire({ text: error });
			}
		});
	});
	$(document).on('change', '.select-delivery-status', function(){
		var id = $(this).data('id');
		var status = $(this).val();

		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + id + '/delivery-status',
			method: 'POST',
			data: {
				status: status
			},
			success: function(data){
				if(data.status){
					ToastMessage.fire({ text: 'Estado actualizado' })
						.then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
					location.reload();
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error al actualizar el estado' });
				location.reload();
			}
		});
	});

</script>
@endsection

