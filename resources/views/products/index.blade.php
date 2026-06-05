@extends('template.app')

@section('title', 'Productos')

@section('content')
<nav class="mb-2">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
		<li class="breadcrumb-item active">Productos</li>
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
					<th>Nombre</th>
					<th>Precio</th>
					<th>Stock</th>
					<th>¿Reduce stock?</th>
					<th>¿Es prestable?</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($products->count() > 0)
				@foreach($products as $product)
				<tr>
					<td>{{ $product->name }}</td>
					<td>S/{{ $product->price }}</td>
					<td>{{ $product->stock ?? 'N/A' }}</td>
					<td>
						@if($product->reduces_stock)
						<span class="badge bg-success text-success-fg">Si</span>
						@else
						<span class="badge bg-secondary text-secondary-fg">No</span>
						@endif
					</td>
					<td>
						@if($product->is_loanable)
						<span class="badge bg-success text-success-fg">Si</span>
						@else
						<span class="badge bg-secondary text-secondary-fg">No</span>
						@endif
					</td>
					<td>
						<div class="d-flex gap-2">
							<div class="d-flex gap-2">
								<button class="btn btn-icon btn-edit-corporate btn-edit" data-id="{{ $product->id }}" data-bs-toggle="tooltip" title="Editar">
									<i class="ti ti-pencil icon"></i>
								</button>
								<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $product->id }}" data-bs-toggle="tooltip" title="Eliminar">
									<i class="ti ti-x icon"></i>
								</button>
							</div>
						</div>
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="3" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($products->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $products->withQueryString()->links() }}
	</div>
	@endif
</div>

<div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<form id="storeForm" method="POST">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-main">
                        <i class="ti ti-circle-plus text-primary fs-1"></i>
                        Crear nuevo producto
                    </h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
                <div class="px-3">
                    <p class="text-muted small mb-0 px-1">Completa los datos para registrar un nuevo producto en el catálogo.</p>
                </div>
				<div class="modal-body pt-3">
					<div class="row">
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label fw-bold">Nombre del Producto <span class="text-danger">*</span></label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-package text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" name="name" placeholder="Ej. Filtro de aire Premium" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Stock Inicial (opcional)</label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-database text-muted"></i>
                                    </span>
                                    <input type="number" class="form-control" name="stock" placeholder="0">
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3 mt-4">
								<label class="form-check">
									<input class="form-check-input" type="checkbox" name="reduces_stock" value="1">
									<span class="form-check-label fw-bold">¿Este producto reduce stock?</span>
								</label>
								<div class="form-hint small">Si se marca, el stock se descontará en todas las ventas.</div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3 mt-4">
								<label class="form-check">
									<input class="form-check-input" type="checkbox" name="is_loanable" value="1">
									<span class="form-check-label fw-bold">¿Es prestable?</span>
								</label>
								<div class="form-hint small">Si se marca, se podrá prestar en las ventas con precio 0.</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x icon me-1"></i> Cancelar
                    </button>
					<button type="submit" class="btn btn-brand px-4 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy icon me-1"></i> Guardar Producto
                    </button>
				</div>
			</form>
		</div>
	</div>
</div>

<div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content shadow-lg border-0">
			<form id="editForm" method="POST">
				<div class="modal-header border-0 pb-0">
					<h5 class="modal-title d-flex align-items-center gap-2 fs-2 fw-bold text-main">
                        <i class="ti ti-edit text-warning fs-1"></i>
                        Editar producto
                    </h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
                <div class="px-3">
                    <p class="text-muted small mb-0 px-1">Actualiza la información del producto seleccionado.</p>
                </div>
				<div class="modal-body pt-3">
					<div class="row">
						<div class="col-lg-12">
							<div class="mb-3">
								<label class="form-label fw-bold">Nombre del Producto</label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-package text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" name="name" id="editName" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Precio de Venta</label>
								<div class="input-group">
                                    <span class="input-group-text bg-light text-muted border-end-0 fw-bold">S/</span>
                                    <input type="number" step="0.01" class="form-control" name="price" id="editPrice" required>
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label fw-bold">Stock</label>
								<div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-database text-muted"></i>
                                    </span>
                                    <input type="number" class="form-control" name="stock" id="editStock">
                                </div>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-check">
									<input class="form-check-input" type="checkbox" name="reduces_stock" id="editIsBidon" value="1">
									<span class="form-check-label fw-bold">¿Este producto reduce stock?</span>
								</label>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-check">
									<input class="form-check-input" type="checkbox" name="is_loanable" id="editIsLoanable" value="1">
									<span class="form-check-label fw-bold">¿Es prestable?</span>
								</label>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer border-0">
					<input type="hidden" id="editId">
					<button type="button" class="btn btn-ghost-secondary px-4 fw-bold" data-bs-dismiss="modal">
                        <i class="ti ti-x icon me-1"></i> Cancelar
                    </button>
					<button type="submit" class="btn btn-brand px-4 shadow-sm fw-bold">
                        <i class="ti ti-device-floppy icon me-1"></i> Actualizar Producto
                    </button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection

@section('scripts')
<script>

	$('#storeForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('products.store') }}',
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
			url: '{{ route('products.index') }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				$('#editName').val(data.name);
				$('#editPrice').val(data.price);
				$('#editStock').val(data.stock);
				$('#editIsBidon').prop('checked', data.reduces_stock == 1);
				$('#editIsLoanable').prop('checked', data.is_loanable == 1);
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
			url: '{{ route('products.index') }}' + '/' + id + '',
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

		ToastConfirm.fire({
			text: '¿Estás seguro que deseas borrar el registro?',
		}).then((result) => {
			if(result.isConfirmed){
				$.ajax({
					url: '{{ route('products.index') }}' + '/' + id,
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

	});




</script>
@endsection