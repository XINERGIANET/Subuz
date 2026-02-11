@extends('template.app')

@section('title', 'Clientes')

@section('content')
<nav class="mb-2">
	<ol class="breadcrumb">
		<li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
		<li class="breadcrumb-item active">Clientes</li>
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
					<th>RUC o DNI</th>
					<th>Nombre comercial</th>
					<th>Razón social</th>
					<th>Dirección</th>
					<th>Distrito</th>
					<th>Correo electrónico</th>
					<th>Teléfono</th>
					<th>Teléfono 2</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($clients->count() > 0)
				@foreach($clients as $client)
				<tr>
					<td>{{ $client->document }}</td>
					<td>{{ $client->name }}</td>
					<td>{{ $client->business_name }}</td>
					<td>{{ $client->address }}</td>
					<td>{{ $client->district }}</td>
					<td>{{ $client->email }}</td>
					<td>{{ $client->phone }}</td>
					<td>{{ $client->phone_2 }}</td>
					<td>
						<div class="d-flex gap-2">
							<div class="d-flex gap-2">
								<button class="btn btn-icon btn-edit-corporate btn-edit" data-id="{{ $client->id }}">
									<i class="ti ti-pencil icon"></i>
								</button>
								<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $client->id }}">
									<i class="ti ti-x icon"></i>
								</button>
							</div>
						</div>
					</td>		
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="9" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($clients->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $clients->withQueryString()->links() }}
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
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">RUC o DNI</label>
								<input type="text" class="form-control" name="document">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Nombre comercial <span class="text-danger">*</span></label>
								<input type="text" class="form-control" name="name">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Razón social</label>
								<input type="text" class="form-control" name="business_name">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Dirección <span class="text-danger">*</span></label>
								<input type="text" class="form-control" name="address">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Distrito <span class="text-danger">*</span></label>
								<select class="form-select" name="district">
									<option value="">Seleccionar</option>
									<option value="Chiclayo">Chiclayo</option>
									<option value="Lambayeque">Lambayeque</option>
									<option value="Pimentel">Pimentel</option>
								</select>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Correo electrónico</label>
								<input type="text" class="form-control" name="email">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Teléfono</label>
								<input type="text" class="form-control" name="phone">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Teléfono 2</label>
								<input type="text" class="form-control" name="phone_2">
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
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">RUC o DNI</label>
								<input type="text" class="form-control" name="document" id="editDocument">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Nombre comercial <span class="text-danger">*</span></label>
								<input type="text" class="form-control" name="name" id="editName">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Razón social</label>
								<input type="text" class="form-control" name="business_name" id="editBusinessName">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Dirección <span class="text-danger">*</span></label>
								<input type="text" class="form-control" name="address" id="editAddress">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Distrito</label>
								<select class="form-select" name="district" id="editDistrict">
									<option value="">Seleccionar</option>
									<option value="Chiclayo">Chiclayo</option>
									<option value="Lambayeque">Lambayeque</option>
									<option value="Pimentel">Pimentel</option>
								</select>
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Correo electrónico</label>
								<input type="text" class="form-control" name="email" id="editEmail">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Teléfono</label>
								<input type="text" class="form-control" name="phone" id="editPhone">
							</div>
						</div>
						<div class="col-lg-6">
							<div class="mb-3">
								<label class="form-label">Teléfono 2</label>
								<input type="text" class="form-control" name="phone_2" id="editPhone2">
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

	$('#storeForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('clients.store') }}',
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
			url: '{{ route('clients.index') }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				$('#editDocument').val(data.document);
				$('#editName').val(data.name);
				$('#editBusinessName').val(data.business_name);
				$('#editAddress').val(data.address);
				$('#editDistrict').val(data.district);
				$('#editEmail').val(data.email);
				$('#editPhone').val(data.phone);
				$('#editPhone2').val(data.phone_2);
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
			url: '{{ route('clients.index') }}' + '/' + id + '',
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
					url: '{{ route('clients.index') }}' + '/' + id,
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