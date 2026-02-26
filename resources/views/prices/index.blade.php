@extends('template.app')

@section('title', 'Precios especiales')

@section('content')
<nav class="mb-2">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
		<li class="breadcrumb-item active">Precios especiales</li>
	</ol>
</nav>
<div class="card">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			<button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#createModal">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</button>
		</div>
		<div>
			<form>
				<div class="input-group">
					<input type="text" class="form-control" placeholder="Buscar" name="search" value="{{ request()->search }}">
					<button type="submit" class="btn btn btn-icon">
						<i class="ti ti-search icon"></i>
					</button>
				</div>
			</form>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>Cliente</th>
					<th>Producto</th>
					<th>Precio</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($prices->count() > 0)
				@foreach($prices as $price)
				<tr>
					<td>{{ optional($price->client)->name }}</td>
					<td>{{ optional($price->product)->name }}</td>
					<td>{{ $price->price }}</td>					
					<td>
						<div class="d-flex gap-2">
							<div class="d-flex gap-2">
								<button class="btn btn-icon btn-edit-corporate btn-edit" data-id="{{ $price->id }}" data-bs-toggle="tooltip" title="Editar">
									<i class="ti ti-pencil icon"></i>
								</button>
								<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $price->id }}" data-bs-toggle="tooltip" title="Eliminar">
									<i class="ti ti-x icon"></i>
								</button>
							</div>
						</div>
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="4" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($prices->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $prices->withQueryString()->links() }}
	</div>
	@endif
</div>

<div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<form id="storeForm" method="POST">
				<div class="modal-header">
					<h5 class="modal-title">Crear nuevo</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label">Cliente</label>
								<select class="form-select ts-clients" name="client_id">
									<option value="">Seleccionar</option>
								</select>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Producto</label>
								<select class="form-select ts-products" name="product_id">
									<option value="">Seleccionar</option>
									@foreach($products as $product)
									<option value="{{ $product->id }}">{{ $product->name }} - S/{{ $product->price }}</option>
									@endforeach
								</select>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Precio</label>
								<input type="text" class="form-control" name="price">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
					<button type="submit" class="btn btn-brand"><i class="ti ti-device-floppy icon"></i> Guardar</button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<form id="editForm" method="POST">
				<div class="modal-header">
					<h5 class="modal-title">Editar</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label">Cliente</label>
								<input type="text" class="form-control" disabled id="editClient">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Producto</label>
								<input type="text" class="form-control" disabled id="editProduct">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Precio</label>
								<input type="text" class="form-control" name="price" id="editPrice">
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<input type="hidden" id="editId">
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

		new TomSelect('.ts-products', {
			valueField: 'id',
			labelField: 'name',
			searchField: 'name',
			copyClassesToDropdown: false,
			dropdownClass: 'dropdown-menu ts-dropdown',
			optionClass:'dropdown-item',
			render: {
				no_results: function(data, escape){
					return '<div class="no-results">No se encontraron resultados</div>'
				}
			}
		});
	});


	$('#storeForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('prices.store') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#createModal').modal('hide');
					$('#storeForm')[0].reset();
					
					ToastMessage.fire({ text: 'Registro guardado' })
						.then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$(document).on('click', '.btn-edit', function(){

		var id = $(this).data('id');

		$.ajax({
			url: '{{ route('prices.index') }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				$('#editClient').val(data.client.name);
				$('#editProduct').val(data.product.name);
				$('#editPrice').val(data.price);
				$('#editId').val(data.id);
				$('#editModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$('#editForm').submit(function(e){
		e.preventDefault();

		var id = $('#editId').val();

		$.ajax({
			url: '{{ route('prices.index') }}' + '/' + id + '',
			method: 'PATCH',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#editModal').modal('hide');
					$('#editForm')[0].reset();
					ToastMessage.fire({ text: 'Registro actualizado' })
						.then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error' });
			}
		});

	});

	$(document).on('click', '.btn-delete', function(){

		var id = $(this).data('id');

		if(confirm('¿Estás seguro que deseas borrar el registro?')){

			$.ajax({
				url: '{{ route('prices.index') }}' + '/' + id,
				method: 'DELETE',
				success: function(data){
					ToastMessage.fire({ text: 'Registro eliminado' })
						.then(() => location.reload());
				},
				error: function(err){
					ToastError.fire({ text: 'Ocurrió un error' });
				}
			});

		}

	});

</script>
@endsection