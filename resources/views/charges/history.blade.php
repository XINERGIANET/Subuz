@extends('template.app')

@php
	$type = request()->type;
	$title = 'Historial de Pagos';
	if($type == 'Credito') $title = 'Historial de Créditos';
	if($type == 'Contado') $title = 'Historial de Contado';
@endphp

@section('title', $title)

@section('content')
<nav class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item">Cobranzas</li>
	@if($type == 'Credito')
    <li class="breadcrumb-item"><a href="{{ route('charges.credit') }}">Crédito</a></li>
	@elseif($type == 'Contado')
	<li class="breadcrumb-item"><a href="{{ route('charges.pending') }}">Pendiente de pago</a></li>
	@endif
    <li class="breadcrumb-item active">Historial</li>
  </ol>
</nav>

<div class="row row-cards mb-4">
    <div class="col-md-4">
        <div class="card metric-card border-0 shadow-sm overflow-hidden">
            <div class="card-status-start bg-primary"></div>
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-primary-lt text-primary avatar avatar-md">
                            <i class="ti ti-cash fs-2"></i>
                        </div>
                    </div>
                    <div class="col text-truncate">
                        <div class="text-uppercase text-muted small fw-bold">Total Recaudado</div>
                        <div class="h2 mb-0 fw-bold">S/{{ number_format($total, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fw-bold"><i class="ti ti-filter me-2"></i>Filtros de Búsqueda</h3>
        <a class="btn btn-success btn-pill px-4 shadow-sm" href="{{ route('payments.excel', request()->all()) }}">
            <i class="ti ti-file-spreadsheet icon me-1"></i> Excel
        </a>
        <a class="btn btn-danger btn-pill px-4 shadow-sm" href="{{ route('payments.pdf', request()->all()) }}">
            <i class="ti ti-file-type-pdf icon me-1"></i> PDF
        </a>
    </div>
	<div class="card-body bg-light-lt py-3">
		<form>
			<div class="row g-3 align-items-end">
				<div class="col-md-4">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Cliente</label>
					<select class="form-select ts-clients" name="client_id">
						<option value="">Seleccionar cliente</option>
						@if(isset($selected_client) && $selected_client)
							<option value="{{ $selected_client->id }}" selected>{{ $selected_client->name }}</option>
						@endif
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Desde</label>
					<input type="date" class="form-control" name="start_date" value="{{ request()->start_date }}">
				</div>
				<div class="col-md-2">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Hasta</label>
					<input type="date" class="form-control" name="end_date" value="{{ request()->end_date }}">
				</div>
				<input type="hidden" name="type" value="{{ request()->type }}">
                <div class="col-md-2">
			        <button type="submit" class="btn btn-brand w-100 py-2 fw-bold"><i class="ti ti-filter icon me-1"></i> Filtrar</button>
                </div>
                <div class="col-md-2 text-md-end">
                    <a href="{{ url()->current() }}?type={{ request()->type }}" class="btn btn-outline-secondary w-100 py-2 fw-medium">
                        <i class="ti ti-refresh icon me-1"></i> Limpiar
                    </a>
                </div>
			</div>
		</form>
	</div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header border-0 py-3">
        <h3 class="card-title fw-bold"><i class="ti ti-list me-2"></i>Detalle de Movimientos</h3>
    </div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th width="50">#</th>
					<th>Cliente</th>
					<th>Guía/Venta</th>
					<th class="text-center">Monto</th>
					<th class="text-center">Forma de pago</th>
					<th class="text-center">Fecha</th>
					@if(auth()->user()->hasRole('admin'))
					<th class="text-end" width="80">Acción</th>
					@endif
				</tr>
			</thead>
			<tbody>
				@forelse($payments as $payment)
				<tr>
					<td class="text-muted">{{ $loop->iteration }}</td>
					<td class="fw-bold">{{ optional(optional($payment->sale)->client)->name ?? 'Consumidor Final' }}</td>
                    <td>
                        <span class="text-muted small">
                            {{ optional($payment->sale)->guide ?? 'Venta Manual' }}
                        </span>
                    </td>
					<td class="text-center fw-bold text-primary">S/{{ number_format($payment->amount, 2) }}</td>
					<td class="text-center">
                        <span class="badge bg-blue-lt px-2 py-1">
                            {{ optional($payment->payment_method)->name ?? 'N/A' }}
                        </span>
                    </td>
					<td class="text-center">{{ $payment->date->format('d/m/Y') }}</td>
					@if(auth()->user()->hasRole('admin'))
					<td class="text-end">
						<button class="btn btn-icon btn-outline-danger btn-delete-payment" data-id="{{ $payment->id }}" data-bs-toggle="tooltip" title="Revertir Pago">
							<i class="ti ti-arrow-back-up icon fs-2"></i>
						</button>
					</td>
					@endif
				</tr>
                @empty
				<tr>
					<td colspan="{{ auth()->user()->hasRole('admin') ? 7 : 6 }}" align="center" class="py-5 text-muted">
                        <i class="ti ti-alert-circle fs-1 mb-2 d-block"></i>
                        No se han encontrado resultados
                    </td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
	@if($payments->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $payments->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection

@section('scripts')
<script>
	$(document).ready(function () {

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

	$(document).on('click', '.btn-delete-payment', function(e){
		e.preventDefault();
		var payment_id = $(this).data('id');

		Swal.fire({
			title: '¿Revertir este pago?',
			text: 'El monto se volverá a sumar a la deuda pendiente de la venta y se actualizará el movimiento de caja.',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#d63939',
			cancelButtonColor: '#6c757d',
			confirmButtonText: '<i class="ti ti-arrow-back-up me-1"></i> Sí, revertir pago',
			cancelButtonText: 'Cancelar',
			reverseButtons: true,
			customClass: {
				confirmButton: 'btn btn-danger px-4',
				cancelButton: 'btn btn-secondary px-4'
			},
			buttonsStyling: false
		}).then((result) => {
			if (result.isConfirmed) {
				Swal.fire({
					title: 'Procesando...',
					text: 'Revirtiendo el pago, por favor espere.',
					allowOutsideClick: false,
					didOpen: () => {
						Swal.showLoading();
					}
				});

				$.ajax({
					url: '{{ url("payments") }}/' + payment_id,
					method: 'DELETE',
					data: {
						_token: '{{ csrf_token() }}'
					},
					success: function(data){
						if(data.status){
							Swal.fire({
								title: '¡Pago Revertido!',
								text: 'El pago ha sido cancelado y la deuda fue restaurada correctamente.',
								icon: 'success',
								confirmButtonColor: '#206bc4',
								confirmButtonText: 'Aceptar',
								customClass: {
									confirmButton: 'btn btn-primary px-4'
								},
								buttonsStyling: false
							}).then(() => {
								location.reload();
							});
						} else {
							Swal.fire({
								title: 'Error',
								text: data.error || 'No se pudo revertir el pago.',
								icon: 'error',
								confirmButtonColor: '#d63939',
								customClass: {
									confirmButton: 'btn btn-danger px-4'
								},
								buttonsStyling: false
							});
						}
					},
					error: function(err){
						console.log(err);
						Swal.fire({
							title: 'Error',
							text: 'Ocurrió un error inesperado al intentar revertir el pago.',
							icon: 'error',
							confirmButtonColor: '#d63939',
							customClass: {
								confirmButton: 'btn btn-danger px-4'
							},
							buttonsStyling: false
						});
					}
				});
			}
		});
	});
</script>
@endsection