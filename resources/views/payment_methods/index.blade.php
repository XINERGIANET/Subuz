@extends('template.app')

@section('title', 'Cuentas')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Cuentas</li>
  </ol>
</nav>
<div class="card">
	<div class="card-header d-flex justify-content-between">
		<div>
			<button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#createModal">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</button>
		</div>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>#</th>
					<th>Nombre de la Cuenta</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($payment_methods->count() > 0)
					@foreach($payment_methods as $method)
					<tr>
						<td>{{ $loop->iteration }}</td>
						<td>{{ $method->name }}</td>
						<td>
							<div class="d-flex gap-2">
								<button class="btn btn-icon btn-edit-corporate btn-edit" data-id="{{ $method->id }}">
									<i class="ti ti-pencil icon"></i>
								</button>
								<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $method->id }}">
									<i class="ti ti-x icon"></i>
								</button>
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
	@if($payment_methods->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $payment_methods->links() }}
	</div>
	@endif
</div>

{{-- Create Modal --}}
<div class="modal modal-blur fade" id="createModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="storeForm" method="POST">
  			<div class="modal-header">
  			  <h5 class="modal-title">Crear nueva cuenta</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  			  <div class="mb-3">
  			  	<label class="form-label">Nombre de la Cuenta</label>
  			  	<input type="text" class="form-control" name="name" placeholder="Ej: BCP, Yape, Efectivo...">
  			  </div>
  			</div>
  			<div class="modal-footer">
  			  <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
  			  <button type="submit" class="btn btn-brand">Guardar</button>
  			</div>
  		</form>
    </div>
  </div>
</div>

{{-- Edit Modal --}}
<div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="editForm" method="POST">
  			<div class="modal-header">
  			  <h5 class="modal-title">Editar cuenta</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  			  <div class="mb-3">
  			  	<label class="form-label">Nombre de la Cuenta</label>
  			  	<input type="text" class="form-control" name="name" id="editName">
  			  </div>
  			</div>
  			<div class="modal-footer">
  				<input type="hidden" id="editId">
  			  <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
  			  <button type="submit" class="btn btn-brand">Guardar</button>
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
			url: '{{ route('payment_methods.store') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#createModal').modal('hide');
					$('#storeForm')[0].reset();
					ToastMessage.fire({ text: 'Cuenta guardada' }).then(() => location.reload());
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
			url: '{{ url('payment_methods') }}/' + id + '/edit',
			method: 'GET',
			success: function(data){
				$('#editName').val(data.name);
				$('#editId').val(data.id);
				$('#editModal').modal('show');
			}
		});
	});

	$('#editForm').submit(function(e){
		e.preventDefault();
		var id = $('#editId').val();
		$.ajax({
			url: '{{ url('payment_methods') }}/' + id,
			method: 'PATCH',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#editModal').modal('hide');
					ToastMessage.fire({ text: 'Cuenta actualizada' }).then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			}
		});
	});

	$(document).on('click', '.btn-delete', function(){
		var id = $(this).data('id');
		ToastConfirm.fire({
			text: '¿Estás seguro que deseas borrar esta cuenta?',
		}).then((result) => {
			if(result.isConfirmed){
				$.ajax({
					url: '{{ url('payment_methods') }}/' + id,
					method: 'DELETE',
					success: function(data){
						ToastMessage.fire({ text: 'Cuenta eliminada' }).then(() => location.reload());
					}
				});
			}
		});
	});
</script>
@endsection
