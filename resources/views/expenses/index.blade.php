@extends('template.app')

@section('title', 'Gastos')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Gastos</li>
  </ol>
</nav>
<div class="card">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
			<button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#createModal">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</button>
			<a class="btn btn-success" href="{{ route('expenses.excel') }}">
				<i class="ti ti-download icon"></i> Excel
			</a>
			@endif
		</div>
		<div class="text-center">
			<span class="d-block small">
				Tienes un total de
			</span>
			<span class="fs-2 fw-bold text-primary">
					S/{{ number_format($total_expenses, 2) }}
				</span>
		</div>
	</div>
	<div class="card-body border-bottom">
		<form class="mb-3">
			<div class="row">
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Mes</label>
						<select class="form-select" name="month">
							<option value="">Seleccionar</option>
							<option value="1" @if(request()->month == 1) selected @endif>Enero</option>
							<option value="2" @if(request()->month == 2) selected @endif>Febrero</option>
							<option value="3" @if(request()->month == 3) selected @endif>Marzo</option>
							<option value="4" @if(request()->month == 4) selected @endif>Abril</option>
							<option value="5" @if(request()->month == 5) selected @endif>Mayo</option>
							<option value="6" @if(request()->month == 6) selected @endif>Junio</option>
							<option value="7" @if(request()->month == 7) selected @endif>Julio</option>
							<option value="8" @if(request()->month == 8) selected @endif>Agosto</option>
							<option value="9" @if(request()->month == 9) selected @endif>Septiembre</option>
							<option value="10" @if(request()->month == 10) selected @endif>Octubre</option>
							<option value="11" @if(request()->month == 11) selected @endif>Noviembre</option>
							<option value="12" @if(request()->month == 12) selected @endif>Diciembre</option>
						</select>
					</div>
				</div>
				<div class="col-lg-3">
					<div class="mb-3">
						<label class="form-label">Año</label>
						<select class="form-select" name="year">
							<option value="">Seleccionar</option>
							@for($i = 2023; $i<=2030; $i++)
							<option value="{{ $i }}" @if(request()->year == $i) selected @endif>{{ $i }}</option>
							@endfor
						</select>
					</div>
				</div>
			</div>
			<button type="submit" class="btn btn-brand"><i class="ti ti-filter icon"></i> Filtrar</button>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>Descripción</th>
					<th>Monto</th>
					<th>Forma de pago</th>
					<th>Fecha</th>
					<th>Acción</th>
				</tr>
			</thead>
			<tbody>
				@if($expenses->count() > 0)
					@foreach($expenses->groupBy(function($item){ return $item->description.$item->date->format('Y-m-d H:i:s'); }) as $group)
					@php $first = $group->first(); @endphp
					<tr>
						<td>{{ $first->description }}</td>
						<td class="fw-bold text-dark">S/{{ number_format($group->sum('amount'), 2) }}</td>
						<td>
							<div class="d-flex flex-column gap-1">
								@foreach($group as $item)
								<span class="badge bg-blue-lt fw-normal" style="text-transform: none;">
									<span class="fw-bold">S/{{ number_format($item->amount, 2) }}</span>
									<span class="ms-1 opacity-75">({{ optional($item->payment_method)->name }})</span>
								</span>
								@endforeach
							</div>
						</td>
						<td>{{ $first->date->format('d/m/Y') }}</td>
						<td>
							@if(auth()->user()->hasRole('admin'))
								<div class="d-flex gap-2">
									<button class="btn btn-icon btn-edit-corporate btn-edit" data-id="{{ $first->id }}" data-bs-toggle="tooltip" title="Editar">
										<i class="ti ti-pencil icon"></i>
									</button>
									<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $first->id }}" data-bs-toggle="tooltip" title="Eliminar">
										<i class="ti ti-x icon"></i>
									</button>
								</div>
							@endif
						</td>		
					</tr>
					@endforeach
				@else
				<tr>
					<td colspan="5" align="center">No se han encontrado resultados</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($expenses->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $expenses->withQueryString()->links() }}
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
  			  <div class="mb-3">
  			  	<label class="form-label">Descripción</label>
  			  	<input type="text" class="form-control" name="description" required>
  			  </div>
  			  
  			  <div class="d-flex justify-content-between align-items-center mb-2">
  			  	<label class="form-label mb-0 fw-bold text-uppercase small">Pagos / Métodos</label>
  			  	<button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-payment-create">
  			  		<i class="ti ti-plus me-1"></i> Agregar pago
  			  	</button>
  			  </div>
  			  
  			  <div id="payment-rows-create">
  			  	<!-- Payment rows will be injected here -->
  			  </div>

  			  <div class="mt-3 p-2 rounded bg-primary-lt border border-primary d-flex justify-content-between align-items-center">
  			  	<span class="fw-bold">Monto Total:</span>
  			  	<span class="h4 mb-0 fw-extrabold" id="total-amount-create">S/0.00</span>
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

<div class="modal modal-blur fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="editForm" method="POST">
  			<div class="modal-header">
  			  <h5 class="modal-title">Editar</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  			  <div class="mb-3">
  			  	<label class="form-label">Descripción</label>
  			  	<input type="text" class="form-control" name="description" id="editDescription" required>
  			  </div>
  			  
  			  <div class="d-flex justify-content-between align-items-center mb-2">
  			  	<label class="form-label mb-0 fw-bold text-uppercase small">Pagos / Métodos (Modo edición)</label>
  			  	<button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-payment-edit">
  			  		<i class="ti ti-plus me-1"></i> Agregar pago
  			  	</button>
  			  </div>
  			  
  			  <div id="payment-rows-edit">
  			  	<!-- Payment rows will be injected here -->
  			  </div>

  			  <div class="mt-3 p-2 rounded bg-primary-lt border border-primary d-flex justify-content-between align-items-center">
  			  	<span class="fw-bold">Monto Total:</span>
  			  	<span class="h4 mb-0 fw-extrabold" id="total-amount-edit">S/0.00</span>
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

	var paymentMethodsHtml = `
		@foreach($payment_methods as $pm)
			<option value="{{ $pm->id }}">{{ $pm->name }}</option>
		@endforeach
	`;

	function addPaymentRowCreate(amount = '') {
		var rowCount = $('#payment-rows-create .payment-row').length;
		var html = `
			<div class="payment-row mb-2 border-bottom pb-2">
				<div class="row g-2 align-items-center">
					<div class="col-7">
						<select class="form-select" name="payments[${rowCount}][method_id]" required>
							<option value="">Cuenta</option>
							${paymentMethodsHtml}
						</select>
					</div>
					<div class="col-4">
						<div class="input-group input-group-flat">
							<span class="input-group-text ps-2 pe-0">S/</span>
							<input type="number" step="0.01" class="form-control ps-1 txt-payment-amount-create" name="payments[${rowCount}][amount]" value="${amount}" placeholder="0.00" required>
						</div>
					</div>
					<div class="col-1 text-end">
						${rowCount > 0 ? '<button type="button" class="btn btn-ghost-danger btn-icon btn-sm btn-remove-payment-create"><i class="ti ti-trash fs-3"></i></button>' : ''}
					</div>
				</div>
			</div>
		`;
		$('#payment-rows-create').append(html);
		calculateTotalCreate();
	}

	function calculateTotalCreate() {
		var total = 0;
		$('.txt-payment-amount-create').each(function() {
			total += parseFloat($(this).val()) || 0;
		});
		$('#total-amount-create').text('S/' + total.toFixed(2));
	}

	$(document).on('click', '#btn-add-payment-create', function() {
		addPaymentRowCreate();
	});

	$(document).on('click', '.btn-remove-payment-create', function() {
		$(this).closest('.payment-row').remove();
		calculateTotalCreate();
	});

	$(document).on('input', '.txt-payment-amount-create', function() {
		calculateTotalCreate();
	});

	$('#createModal').on('show.bs.modal', function () {
		if($('#payment-rows-create').is(':empty')) {
			addPaymentRowCreate();
		}
	});

	$('#storeForm').submit(function(e){
		e.preventDefault();

		$.ajax({
			url: '{{ route('expenses.store') }}',
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if(data.status){
					$('#createModal').modal('hide');
					$('#storeForm')[0].reset();
					$('#payment-rows-create').empty();
					
					ToastMessage.fire({ text: 'Gasto registrado correctamente' })
						.then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
				}
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error al guardar' });
			}
		});

	});

	function addPaymentRowEdit(methodId = '', amount = '') {
		var rowCount = $('#payment-rows-edit .payment-row').length;
		var html = `
			<div class="payment-row mb-2 border-bottom pb-2">
				<div class="row g-2 align-items-center">
					<div class="col-7">
						<select class="form-select" name="payments[${rowCount}][method_id]" required>
							<option value="">Cuenta</option>
							${paymentMethodsHtml}
						</select>
					</div>
					<div class="col-4">
						<div class="input-group input-group-flat">
							<span class="input-group-text ps-2 pe-0">S/</span>
							<input type="number" step="0.01" class="form-control ps-1 txt-payment-amount-edit" name="payments[${rowCount}][amount]" value="${amount}" placeholder="0.00" required>
						</div>
					</div>
					<div class="col-1 text-end">
						<button type="button" class="btn btn-ghost-danger btn-icon btn-sm btn-remove-payment-edit"><i class="ti ti-trash fs-3"></i></button>
					</div>
				</div>
			</div>
		`;
		var $row = $(html);
		if(methodId) $row.find('select').val(methodId);
		$('#payment-rows-edit').append($row);
		calculateTotalEdit();
	}

	function calculateTotalEdit() {
		var total = 0;
		$('.txt-payment-amount-edit').each(function() {
			total += parseFloat($(this).val()) || 0;
		});
		$('#total-amount-edit').text('S/' + total.toFixed(2));
	}

	$(document).on('click', '#btn-add-payment-edit', function() {
		addPaymentRowEdit();
	});

	$(document).on('click', '.btn-remove-payment-edit', function() {
		$(this).closest('.payment-row').remove();
		calculateTotalEdit();
	});

	$(document).on('input', '.txt-payment-amount-edit', function() {
		calculateTotalEdit();
	});

	$(document).on('click', '.btn-edit', function(){

		var id = $(this).data('id');
		$('#payment-rows-edit').empty();

		$.ajax({
			url: '{{ route('expenses.index') }}' + '/' + id + '/edit/',
			method: 'GET',
			success: function(data){
				$('#editDescription').val(data.description);
				$('#editId').val(data.id);
				
				// Initialize with all payments in the group
				data.payments.forEach(function(p){
					addPaymentRowEdit(p.method_id, p.amount);
				});
				
				$('#editModal').modal('show');
			},
			error: function(err){
				ToastError.fire({ text: 'Ocurrió un error al cargar datos' });
			}
		});

	});

	$('#editForm').submit(function(e){
		e.preventDefault();

		var id = $('#editId').val();

		$.ajax({
			url: '{{ route('expenses.index') }}' + '/' + id + '',
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
					url: '{{ route('expenses.index') }}' + '/' + id,
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




	$(document).ready(function(){
		const urlParams = new URLSearchParams(window.location.search);
		if (urlParams.has('create')) {
			$('#createModal').modal('show');
		}
	});

</script>
@endsection