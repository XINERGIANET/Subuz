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
<style>
	.ts-dropdown {
		z-index: 2000 !important;
		background: var(--bg-primary) !important;
	}
	.ts-dropdown .dropdown-item {
		color: var(--text-main) !important;
	}
	.ts-dropdown .dropdown-item:hover {
		background-color: rgba(0, 0, 0, 0.05) !important;
	}
	[data-bs-theme='dark'] .ts-dropdown .dropdown-item:hover {
		background-color: rgba(255, 255, 255, 0.1) !important;
	}
	.card-filter-container {
		overflow: visible !important;
	}
</style>
<div class="card mb-4 card-filter-container">
	<div class="card-body">
		<div class="row">
			<div class="col-lg-2">
				<div class="mb-3">
					<label class="form-label">Orden de venta</label>
					<input type="text" class="form-control" value="{{ $order }}" disabled>
				</div>
			</div>
			<div class="col-lg-3" style="display:none">
				<div class="mb-3">
					<label class="form-label">Tipo de venta</label>
					<select class="form-select" id="type">
						<option value="">Seleccionar</option>
						<option value="Contado" selected>Contado</option>
						<option value="Credito">Crédito</option>
					</select>
				</div>
			</div>
			<div class="col-lg-2">
				<div class="mb-3">
					<label class="form-label">Fecha</label>
					<input type="date" class="form-control" id="date" value="{{ now()->format('Y-m-d') }}">
				</div>
			</div>
			<div class="col-lg-3">
				<div class="mb-3">
					<label class="form-label d-flex justify-content-between align-items-center">
						Cliente
						<span id="client-type-badge"></span>
					</label>
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
			<div class="col-lg-2">
				<div class="mb-3">
					<label class="form-label">Despachador</label>
					<select class="form-select" id="dispatcher_id">
						<option value="">Cualquiera</option>
						@foreach($dispatchers as $dispatcher)
						<option value="{{ $dispatcher->id }}">{{ $dispatcher->name }}</option>
						@endforeach
					</select>
				</div>
			</div>
			<div class="col-lg-3">
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
					<th>Estado</th>
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
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label">Tipo de cliente <span class="text-danger">*</span></label>
								<select class="form-select" name="type" required>
									<option value="Contado">Contado</option>
									<option value="Credito">Crédito</option>
								</select>
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
<div class="modal modal-blur fade" id="splitModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-sm modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">¿Cuántos deseas prestar?</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<div class="modal-body">
				<div class="row">
					<input type="hidden" id="splitProductId">
					<input type="hidden" id="splitTotalQty">
					<div class="col-6 mb-3">
						<label class="form-label">Prestar</label>
						<input type="number" class="form-control text-center" id="splitLoanQty" min="0">
					</div>
					<div class="col-6 mb-3">
						<label class="form-label">Vender</label>
						<input type="number" class="form-control text-center" id="splitSellQty" min="0">
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary w-100" id="btn-confirm-split">Confirmar</button>
			</div>
		</div>
	</div>
</div>
@endsection

@section('scripts')
<script>
	$(document).ready(function(){
		getItems();
		
		var basePrices = {};
		$('.ts-products option').each(function(){
			if($(this).val()){
				var text = $(this).text();
				var parts = text.split(' - S/');
				basePrices[$(this).val()] = {
					name: parts[0],
					price: parts[1]
				};
			}
		});

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
					$('#type').val(data.type);
					
					var badgeClass = data.type === 'Credito' ? 'bg-purple-lt' : 'bg-azure-lt';
					var badgeText = data.type === 'Credito' ? 'Crédito' : 'Contado';
					$('#client-type-badge').attr('class', 'badge ' + badgeClass).text(badgeText);

					return `<div>${escape(data.name)}</div>`;
				},
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			}
		});

		var tsProducts = new TomSelect('.ts-products', {
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

		tsClients.on('change', function(value){
			if(!value){
				$('#client-type-badge').attr('class', '').text('');
				$('#type').val('Contado');
				updateProductLabels({});
			} else {
				$.get('{{ url("prices/special") }}/' + value, function(prices){
					updateProductLabels(prices);
				});
			}

			// Actualizar todos los precios del carrito
			$.post('{{ route("cart.updatePrices") }}', { client_id: value }, function(){
				getItems();
			});
		});

		function updateProductLabels(specialPrices){
			var specials = {};
			if(Array.isArray(specialPrices)){
				specialPrices.forEach(p => specials[p.product_id] = p.price);
			}

			$('.ts-products option').each(function(){
				var id = $(this).val();
				if(id && basePrices[id]){
					var price = specials[id] ? specials[id] : basePrices[id].price;
					var newName = basePrices[id].name + ' - S/' + price;
					
					// Update underlying option
					$(this).text(newName);
					
					// Update TomSelect
					tsProducts.updateOption(id, { name: newName });
				}
			});
		}
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
					ToastError.fire({ text: data.error ? data.error : 'OcurriÃ³ un error' });
				}
			},
			error: function(err){
				ToastError.fire({ text: 'OcurriÃ³ un error' });
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
							${item.special ? '<span class="badge bg-purple-lt fw-bold mb-1">Precio especial</span><br>' : ''}
							${item.is_loanable ? `
								<label class="form-check mb-0 mt-1">
									<input class="form-check-input cbx-loaned" type="checkbox" data-id="${item.id}" ${item.is_loaned ? 'checked' : ''}>
									<span class="form-check-label small fw-bold">Es prestable</span>
								</label>
							` : ''}
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
							<button class="btn btn-sm btn-icon btn-danger btn-delete" data-id="${item.id}" data-is-loaned="${item.is_loaned}" title="Eliminar">
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
		var client_id = $('#client_id').val();
		$.ajax({
			url: '{{ route('cart.store') }}',
			method: 'POST',
			data: { id:id, client_id: client_id },
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

		var price = $(this).closest('tr').find('.txt-price').val();
		var quantity = $(this).closest('tr').find('.txt-quantity').val();
		var is_loaned = $(this).closest('tr').find('.cbx-loaned').prop('checked');
		
		$.ajax({
			url: '{{ route('cart.update') }}',
			method: 'PATCH',
			data: { id, price, quantity, is_loaned },
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

	$(document).on('change', '.cbx-loaned', function(e){
		var id = $(this).data('id');
		var is_loaned = $(this).prop('checked');
		var quantity = parseInt($(this).closest('tr').find('.txt-quantity').val());

		if (is_loaned && quantity > 1) {
			e.preventDefault();
			$(this).prop('checked', false);

			$('#splitProductId').val(id);
			$('#splitTotalQty').val(quantity);
			$('#splitLoanQty').val(quantity).attr('max', quantity);
			$('#splitSellQty').val(0).attr('max', quantity);
			$('#splitModal').modal('show');
			return;
		}

		var sell_qty = is_loaned ? 0 : quantity;
		var loan_qty = is_loaned ? quantity : 0;
		var client_id = $('#client_id').val();

		$.ajax({
			url: '{{ route('cart.split') }}',
			method: 'POST',
			data: { id, sell_qty, loan_qty, client_id },
			success: function(data){
				if(data.status){
					getItems();
				}
			},
			error: function(err){
				console.log(err);
			}
		});
	});

	$('#btn-confirm-split').click(function(){
		var id = $('#splitProductId').val();
		var sell_qty = $('#splitSellQty').val();
		var loan_qty = $('#splitLoanQty').val();
		var client_id = $('#client_id').val();

		$.ajax({
			url: '{{ route('cart.split') }}',
			method: 'POST',
			data: { id, sell_qty, loan_qty, client_id },
			success: function(data){
				if(data.status){
					$('#splitModal').modal('hide');
					getItems();
				}
			},
			error: function(err){
				console.log(err);
			}
		});
	});

	$('#splitLoanQty').on('input', function(){
		var total = parseInt($('#splitTotalQty').val());
		var loan = parseInt($(this).val()) || 0;
		if(loan > total) { loan = total; $(this).val(total); }
		if(loan < 0) { loan = 0; $(this).val(0); }
		$('#splitSellQty').val(total - loan);
	});
	$('#splitSellQty').on('input', function(){
		var total = parseInt($('#splitTotalQty').val());
		var sell = parseInt($(this).val()) || 0;
		if(sell > total) { sell = total; $(this).val(total); }
		if(sell < 0) { sell = 0; $(this).val(0); }
		$('#splitLoanQty').val(total - sell);
	});

	$(document).on('click', '.btn-delete', function(){

		var id = $(this).data('id');
		var is_loaned = $(this).data('is-loaned') ? 'true' : 'false';

		$.ajax({
			url: '{{ route('cart.destroy') }}',
			method: 'DELETE',
			data: { id, is_loaned },
			success: function(data){
				getItems();
			},
			error: function(err){
				console.log(err);
			}
		});

	});

	$(document).on('click', '#btn-save', function(){

		// var guide = $('#guide').val(); // Removed
		var type = $('#type').val();
		var date = $('#date').val();
		var client_id = $('#client_id').val();
		var dispatcher_id = $('#dispatcher_id').val();
		$.ajax({
			url: '{{ route('sales.store') }}',
			method: 'POST',
			data: { type, date, client_id, dispatcher_id },
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

</script>
@endsection

