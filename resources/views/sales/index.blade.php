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
			<a class="btn btn-primary" href="{{ route('sales.create') }}">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</a>
			<a class="btn btn-success" href="{{ route('sales.excel') }}">
				<i class="ti ti-download icon"></i> Excel
			</a>
			@endif
			@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('despachador'))
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
							<option value="Credito" {{ request()->type == 'Credito' ? 'selected' : '' }}>CrÃ©dito</option>
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
			<button type="submit" class="btn btn-primary"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
	</div>
	@endif
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead>
				<tr>
					<th>#</th>
					<th>Guía de remisión</th>
					<th>Fecha</th>
					<th>Tipo de venta</th>
					<th>Método de pago</th>
					<th>Cliente</th>
					<th>Distrito</th>
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
					<td>{{ optional($sale->client)->district }}</td>
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
							<button class="btn btn-icon btn-show" data-id="{{ $sale->id }}">
								<i class="ti ti-printer icon"></i>
							</button>
							@if(auth()->user()->hasRole('despachador') && !$sale->paid)
							<button class="btn btn-icon btn-dispatch" data-id="{{ $sale->id }}" data-guide="{{ $sale->guide }}" data-total="{{ $sale->total }}">
								<i class="ti ti-check icon"></i>
							</button>
							@endif
							@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
							<button class="btn btn-icon btn-edit" data-id="{{ $sale->id }}">
								<i class="ti ti-edit icon"></i>
							</button>
							<button class="btn btn-icon btn-delete" data-id="{{ $sale->id }}">
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
  		  	<thead>
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
  		  	<thead>
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
  		  <button class="btn btn-primary" id="btn-save">Guardar</button>
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
  				<div class="mb-2 text-muted">
  					Venta: <span id="dispatch_guide"></span> | Total: S/<span id="dispatch_total"></span>
  				</div>
  				<div class="mb-3">
  					<label class="form-label">Pago?</label>
  					<div class="btn-group w-100" role="group">
  						<input type="radio" class="btn-check" name="paid" id="dispatch_paid_yes" value="1">
  						<label class="btn btn-outline-success" for="dispatch_paid_yes">Si, pago</label>
  						<input type="radio" class="btn-check" name="paid" id="dispatch_paid_no" value="0">
  						<label class="btn btn-outline-danger" for="dispatch_paid_no">No pago</label>
  					</div>
  				</div>
  				<div class="mb-3" id="dispatchPaymentMethod" style="display:none">
  					<label class="form-label">Metodo de pago</label>
  					<select class="form-select" name="payment_method_id">
  						<option value="">Seleccionar</option>
  						@foreach($payment_methods as $payment_method)
  						<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
  						@endforeach
  					</select>
  				</div>
  			</div>
  			<div class="modal-footer">
  				<input type="hidden" name="sale_id" id="dispatch_sale_id">
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

	$(document).on('click', '.btn-dispatch', function(){
		var id = $(this).data('id');
		var guide = $(this).data('guide');
		var total = $(this).data('total');

		$('#dispatch_sale_id').val(id);
		$('#dispatch_guide').text(guide);
		$('#dispatch_total').text(total);
		$('#dispatchForm')[0].reset();
		$('#dispatchPaymentMethod').hide();

		$('#dispatchModal').modal('show');
	});

	$(document).on('change', 'input[name="paid"]', function(){
		if($(this).val() == '1'){
			$('#dispatchPaymentMethod').show();
		}else{
			$('#dispatchPaymentMethod').hide();
			$('select[name="payment_method_id"]').val('');
		}
	});

	$('#dispatchForm').submit(function(e){
		e.preventDefault();

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
					ToastError.fire({ text: data.error ? data.error : 'Ocurrio un error' });
				}
			},
			error: function(){
				ToastError.fire({ text: 'Ocurrio un error' });
			}
		});
	});
</script>
@endsection

