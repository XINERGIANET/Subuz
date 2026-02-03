@extends('template.app')

@section('title', 'Crear venta')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Ventas</a></li>
    <li class="breadcrumb-item active">Crear nuevo</li>
  </ol>
</nav>
<div class="card mb-4">
	<div class="card-body">
		<div class="row">
			<div class="col-lg-3">
				<div class="mb-3">
					<label class="form-label">Orden de venta</label>
					<input type="text" class="form-control" value="{{ $order }}" disabled>
				</div>
			</div>
			<div class="col-lg-3">
				<div class="mb-3">
					<label class="form-label">Guía de remisión</label>
					<div class="input-group">
						<span class="input-group-text">GR-00000</span>
						<input type="text" class="form-control" id="guide">
					</div>
				</div>
			</div>
			<div class="col-lg-3">
				<div class="mb-3">
					<label class="form-label">Tipo de venta</label>
					<select class="form-select" id="type">
						<option value="">Seleccionar</option>
						<option value="Contado">Contado</option>
						<option value="Credito">Crédito</option>
						<option value="Pago pendiente">Pago pendiente</option>
					</select>
				</div>
			</div>
			<div class="col-lg-3" id="paymentMethod" style="display:none">
				<div class="mb-3">
					<label class="form-label">Forma de pago</label>
					<select class="form-select" id="payment_method_id">
						<option value="">Seleccionar</option>
						@foreach($payment_methods as $payment_method)
						<option value="{{ $payment_method->id }}">{{ $payment_method->name }}</option>
						@endforeach
					</select>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="mb-3">
					<label class="form-label">Fecha</label>
					<input type="date" class="form-control" id="date" value="{{ now()->format('Y-m-d') }}">
				</div>
			</div>
			<div class="col-lg-4">
				<div class="mb-3">
					<label class="form-label">Cliente</label>
					<div class="input-group">
						<select class="form-select ts-clients" id="client_id">
							<option value="">Seleccionar</option>
						</select>
						<button class="btn btn-icon" data-bs-toggle="modal" data-bs-target="#createClientModal">
							<i class="ti ti-user-plus icon"></i>
						</button>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="mb-3">
					<label class="form-label">Producto</label>
					<select class="form-select ts-products">
						<option value="">Seleccionar</option>
						@foreach($products as $product)
						<option value="{{ $product->id }}">{{ $product->name }} - S/{{ $product->price }}</option>
						@endforeach
					</select>
				</div>
			</div>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table card-table">
			<thead>
				<tr>
					<th>#</th>
					<th>Nombre</th>
					<th>Especial</th>
					<th>Precio</th>
					<th>Cantidad</th>
					<th>Subtotal</th>
					<th></th>
				</tr>
			</thead>
			<tbody id="tbl-items">
				
			</tbody>
		</table>
	</div>
	
</div>
<div class="col-lg-3 offset-lg-9">
	<div class="card">
		<div class="card-body">
			<div class="row">
				@php
					$total = 100;
				@endphp
				<div class="col-6 mb-2">
					<span class="fw-bold">Subtotal</span>
				</div>
				<div class="col-6 text-end mb-2" id="lbl-subtotal">
				</div>
				<div class="col-6 mb-2">
					<span class="fw-bold">I.G.V.</span>
				</div>
				<div class="col-6 text-end mb-2" id="lbl-igv">
				</div>
				<div class="col-6 mb-2">
					<span class="fw-bold">Total</span>
				</div>
				<div class="col-6 text-end mb-2" id="lbl-total">
				</div>
				<div class="col-12">
					<button class="btn btn-primary w-100" id="btn-save"><i class="ti ti-device-floppy icon"></i> Guardar</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="createClientModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<form id="storeClientForm" method="POST">
				<div class="modal-header">
					<h5 class="modal-title">Crear nuevo cliente</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">RUC o DNI</label>
								<input type="text" class="form-control" name="document">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Nombre o nombre comercial <span class="text-danger">*</span></label>
								<input type="text" class="form-control" name="name">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
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
		getItems();

		var tsClients = new TomSelect('.ts-clients', {
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

		new TomSelect('.ts-products', {
			valueField: 'id',
			labelField: 'name',
			searchField: 'name',
			copyClassesToDropdown: false,
			dropdownClass: 'dropdown-menu ts-dropdown',
    	optionClass:'dropdown-item',
			onItemAdd: function(value, $item){
				addItem(value);
				this.clear();
			},
			render: {
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			}
		});
	});

	$('#storeClientForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('clients.storeInSale') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#createClientModal').modal('hide');
					$('#storeClientForm')[0].reset();
					
					ToastMessage.fire({ text: 'Registro guardado' });
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	function money(value){
		return value.toLocaleString('es-PE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
	}

	function getItems(){
		$.ajax({
			url: '{{ route('cart.index') }}',
			method: 'GET',
			success: function(data){
				var html = '';
				data.items.forEach(function(item, key){
					html += `
					<tr>
						<td>${key + 1}</td>
						<td>${item.name}</td>
						<td>
							<input type="checkbox" class="form-check-input cbx-special" ${item.special ? 'checked' : ''}>
						</td>
						<td>
							<input type="text" class="form-control form-control-sm txt-price" value="${ money(item.price) }" data-id="${item.id}" style="width: 60px;">
						</td>
						<td>
							<input type="text" class="form-control form-control-sm txt-quantity" value="${item.quantity}" data-id="${item.id}" style="width: 60px;">
						</td>
						<td>${ money(item.amount) }</td>
						<td>
							<!-- <button class="btn btn-sm btn-icon btn-primary btn-edit" data-id="${item.id}" title="Actualizar">
								<i class="ti ti-reload"></i>
							</button> -->
							<button class="btn btn-sm btn-icon btn-danger btn-delete" data-id="${item.id}" title="Eliminar">
								<i class="ti ti-x"></i>
							</button>
						</td>
					</tr>
					`;
				});

				$('#tbl-items').html(html);
				$('#lbl-subtotal').text(data.subtotal);
				$('#lbl-igv').text(data.igv);
				$('#lbl-total').text(data.total);
			},
			error: function(err){
				console.log(err);
			}
		});
	}

	function addItem(id){

		$.ajax({
			url: '{{ route('cart.store') }}',
			method: 'POST',
			data: { id:id },
			success: function(data){
				getItems();
			},
			error: function(err){
				console.log(err);
			}
		});

	}

	// $(document).on('click', '.btn-edit', function(){
	// 	var id = $(this).data('id');
	// 	var price = $(this).parent().parent().find('.txt-price').val();
	// 	var quantity = $(this).parent().parent().find('.txt-quantity').val();
	// 	var special = $(this).parent().parent().find('.cbx-special').prop('checked');

	// 	$.ajax({
	// 		url: '{{ route('cart.update') }}',
	// 		method: 'PATCH',
	// 		data: { id, price, quantity, special },
	// 		success: function(data){
	// 			if(data.status){
	// 				getItems();
	// 			}else{
	// 				alert(data.error);
	// 			}
	// 		},
	// 		error: function(err){
	// 			console.log(err);
	// 		}
	// 	});
	// });

	$(document).on('blur', '.txt-price, .txt-quantity', function(){
		var id = $(this).data('id');

		var price = $(this).parent().parent().find('.txt-price').val();
		var quantity = $(this).parent().parent().find('.txt-quantity').val();
		var special = $(this).parent().parent().find('.cbx-special').prop('checked');
		

		$.ajax({
			url: '{{ route('cart.update') }}',
			method: 'PATCH',
			data: { id, price, quantity, special },
			success: function(data){
				if(data.status){
					getItems();
				}else{
					alert(data.error);
				}
			},
			error: function(err){
				console.log(err);
			}
		});
	});

	$(document).on('click', '.btn-delete', function(){

		var id = $(this).data('id');

		$.ajax({
			url: '{{ route('cart.destroy') }}',
			method: 'DELETE',
			data: { id },
			success: function(data){
				getItems();
			},
			error: function(err){
				console.log(err);
			}
		});

	});

	$(document).on('click', '#btn-save', function(){

		var guide = $('#guide').val();
		var type = $('#type').val();
		var payment_method_id = $('#payment_method_id').val();
		var date = $('#date').val();
		var client_id = $('#client_id').val();
		$.ajax({
			url: '{{ route('sales.store') }}',
			method: 'POST',
			data: { guide, type, payment_method_id, date, client_id },
			success: function(data){
				if(data.status){
					location.href = '{{ route('sales.index') }}';
				}else{
					alert(data.error);
				}
			},
			error: function(err){
				console.log(err);
			}
		});

	});

	$("#type").change(function(){

		if($(this).val() == 'Contado'){
			$('#paymentMethod').css('display', 'block');
		}else{
			$('#paymentMethod').css('display', 'none');
			$('#payment_method_id').val('');
		}

	});


</script>
@endsection