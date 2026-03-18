@extends('template.app')

@section('title', 'Ventas')

@section('content')
<nav class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Ventas</li>
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
                            <i class="ti ti-shopping-cart fs-2"></i>
                        </div>
                    </div>
                    <div class="col text-truncate">
                        <div class="text-uppercase text-muted small fw-bold">Total Ventas del Periodo</div>
                        <div class="h2 mb-0 fw-bold text-primary">S/{{ number_format($total_sales, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card metric-card border-0 shadow-sm overflow-hidden">
            <div class="card-status-start bg-danger"></div>
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-danger-lt text-danger avatar avatar-md">
                            <i class="ti ti-ban fs-2"></i>
                        </div>
                    </div>
                    <div class="col text-truncate">
                        <div class="text-uppercase text-muted small fw-bold">Pedidos Anulados</div>
                        <div class="h2 mb-0 fw-bold text-danger">{{ $annulled_count }}</div>
                    </div>
                    @if($annulled_count > 0)
                    <div class="col-auto">
                        <button class="btn btn-sm btn-outline-danger px-3 py-1 fw-bold" data-bs-toggle="modal" data-bs-target="#annulledModal">
                            Detallar
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if(auth()->user()->hasRole('admin'))
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase text-muted small fw-bold">Estado de Caja</div>
                    <div class="mt-1">
                        @if($cashbox)
                        <span class="badge bg-success-lt fw-bold px-2 py-1"><i class="ti ti-circle-check me-1"></i> ABIERTA</span>
                        @else
                        <span class="badge bg-danger-lt fw-bold px-2 py-1"><i class="ti ti-circle-x me-1"></i> CERRADA</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end">
                    <a class="btn btn-primary btn-sm px-3 py-2 fw-bold shadow-sm" href="{{ route('cashbox.index') }}">
                        <i class="ti ti-external-link me-2 fs-3"></i> Gestionar Caja
                    </a>
                    <span class="extra-small text-muted mt-1 italic">Click para ver movimientos y cierres</span>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal Pedidos Anulados -->
<div class="modal modal-blur fade" id="annulledModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
  	<div class="modal-content shadow-lg border-0">
  		<div class="modal-header bg-danger text-white">
  		  <h5 class="modal-title fw-bold"><i class="ti ti-ban me-2"></i>Pedidos Anulados Detallados</h5>
  		  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
  		<div class="modal-body p-0">
  		  <div class="table-responsive">
              <table class="table table-vcenter card-table">
                  <thead class="bg-light">
                      <tr>
                          <th>Guía</th>
                          <th>Fecha</th>
                          <th>Cliente</th>
                          <th class="text-end">Total</th>
                      </tr>
                  </thead>
                  <tbody>
                      @foreach($annulled_sales as $annulled)
                      <tr>
                          <td class="fw-bold">{{ $annulled->guide }}</td>
                          <td class="small">{{ $annulled->date->format('d/m/Y H:i') }}</td>
                          <td>{{ optional($annulled->client)->name ?? 'N/A' }}</td>
                          <td class="text-end fw-bold text-danger">S/{{ number_format($annulled->total, 2) }}</td>
                      </tr>
                      @endforeach
                  </tbody>
              </table>
          </div>
  		</div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary px-4" data-bs-target="#annulledModal" data-bs-toggle="modal">Cerrar</button>
        </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fw-bold"><i class="ti ti-filter me-2"></i>Filtros de Búsqueda</h3>
        <div class="d-flex gap-2">
            @if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller'))
            <a class="btn btn-brand btn-pill px-4 shadow-sm" href="{{ route('sales.create') }}">
                <i class="ti ti-plus me-1 fs-3"></i> Nueva Venta
            </a>
            <a class="btn btn-success btn-pill px-4 shadow-sm" href="{{ route('sales.excel', request()->all()) }}">
                <i class="ti ti-file-spreadsheet me-1 fs-3"></i> Excel
            </a>
            <a class="btn btn-danger btn-pill px-4 shadow-sm" href="{{ route('sales.pdf', request()->all()) }}">
                <i class="ti ti-file-type-pdf me-1 fs-3"></i> PDF
            </a>
            @endif
        </div>
    </div>
	@if(!auth()->user()->hasRole('despachador'))
	<div class="card-body bg-white py-3 border-bottom border-top">
		<form>
			<div class="row g-3 align-items-end">
				<div class="col-md-3">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Cliente</label>
					<select class="form-select ts-clients" name="client_id">
						<option value="">Seleccionar cliente</option>
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Tipo de venta</label>
					<select class="form-select text-dark" name="type">
						<option value="">Todos</option>
						<option value="Contado" {{ request()->type == 'Contado' ? 'selected' : '' }}>Contado</option>
						<option value="Credito" {{ request()->type == 'Credito' ? 'selected' : '' }}>Crédito</option>
						<option value="Pago pendiente" {{ request()->type == 'Pago pendiente' ? 'selected' : '' }}>Pago pendiente</option>
					</select>
				</div>
				<div class="col-md-2">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Desde</label>
					<input type="date" class="form-control" name="start_date" value="{{ request()->start_date ? request()->start_date : now()->format('Y-m-d') }}">
				</div>
				<div class="col-md-2">
					<label class="form-label small fw-medium text-muted text-uppercase mb-1">Hasta</label>
					<input type="date" class="form-control" name="end_date" value="{{ request()->end_date ? request()->end_date : now()->format('Y-m-d') }}">
				</div>
                <div class="col-md-1">
			        <button type="submit" class="btn btn-brand w-100 py-2 fw-bold"><i class="ti ti-search fs-3"></i></button>
                </div>
                <div class="col-md-2 text-md-end">
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary w-100 py-2 fw-medium">
                        <i class="ti ti-refresh icon me-1"></i> Limpiar
                    </a>
                </div>
			</div>
		</form>
	</div>
	@endif
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header border-0 py-3">
        <h3 class="card-title fw-bold"><i class="ti ti-list me-2"></i>Historial de Ventas</h3>
    </div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th width="50">#</th>
					<th>Guía</th>
					<th>Fecha</th>
					<th class="text-center">Tipo</th>
					<th>Cliente</th>
					<th class="text-center">Despacho</th>
					<th class="text-center">Pago</th>
					<th class="text-center">Total</th>
					<th class="text-end">Acciones</th>
				</tr>
			</thead>
			<tbody>
				@forelse($sales as $sale)
				<tr>
					<td class="text-muted">{{ $loop->iteration }}</td>
					<td class="fw-bold text-dark">{{ $sale->guide }}</td>
					<td class="small">{{ $sale->date->format('d/m/Y') }}</td>
					<td class="text-center">
                        <span class="badge @if($sale->type=='Contado') bg-azure-lt @elseif($sale->type=='Credito') bg-purple-lt @else bg-orange-lt @endif px-2">
                            {{ $sale->type }}
                        </span>
                    </td>
					<td class="fw-medium text-dark">{{ optional($sale->client)->name ?? 'Consumidor Final' }}</td>
					<td class="text-center">
						@php
							$isDelivered = $sale->paid || $sale->type == 'Pago pendiente' || $sale->movements->where('type', 'debt')->isNotEmpty();
						@endphp
						@if($sale->status == 'Anulado')
							<span class="badge bg-danger-lt">Anulado</span>
						@elseif($isDelivered)
							<span class="badge bg-success-lt">Entregado</span>
						@else
							<span class="badge bg-warning-lt">No entregado</span>
						@endif
					</td>
					<td class="text-center">
						@if($sale->status == 'Anulado')
							<span class="text-muted"><i class="ti ti-minus"></i></span>
						@elseif($sale->paid)
							<span class="text-success"><i class="ti ti-circle-check fs-2"></i></span>
						@else
							<span class="text-danger"><i class="ti ti-circle-x fs-2"></i></span>
						@endif
					</td>
					<td class="text-center fw-extrabold text-primary">S/{{ number_format($sale->total, 2) }}</td>
					<td class="text-end">
						<div class="d-flex justify-content-end gap-1">
							<button class="btn btn-icon btn-outline-primary btn-sm btn-show" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Imprimir">
								<i class="ti ti-printer fs-2"></i>
							</button>
							@if($sale->status != 'Anulado')
								@if(auth()->user()->hasRole('despachador') && !$isDelivered)
								<button class="btn btn-icon btn-brand btn-sm btn-dispatch" data-id="{{ $sale->id }}" data-guide="{{ $sale->guide }}" data-total="{{ $sale->total }}" data-type="{{ $sale->type }}" data-bs-toggle="tooltip" title="Despachar/Entregar">
									<i class="ti ti-check text-white fs-2"></i>
								</button>
								@endif
								@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller') || auth()->user()->hasRole('despachador'))
									@if(!$isDelivered)
									<button class="btn btn-icon btn-outline-info btn-sm btn-edit" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Editar">
										<i class="ti ti-edit fs-2"></i>
									</button>
									<button class="btn btn-icon btn-outline-danger btn-sm btn-delete" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Anular">
										<i class="ti ti-ban fs-2"></i>
									</button>
									@endif
								@endif
							@endif
						</div>
					</td>		
				</tr>
                @empty
				<tr>
					<td colspan="9" align="center" class="py-5 text-muted">
                        <i class="ti ti-mood-neutral fs-1 mb-2 d-block"></i>
                        No se han encontrado registros de ventas para este filtro
                    </td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
	@if($sales->hasPages())
	<div class="card-footer d-flex align-items-center justify-content-center border-0">
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
   				<div class="mb-3" id="payment-status-wrapper">
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

<div class="modal modal-blur fade" id="confirmAnnulModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      <div class="modal-status bg-danger"></div>
      <div class="modal-body text-center py-4">
        <i class="ti ti-alert-triangle fs-1 text-danger mb-3"></i>
        <h3>¿Estás seguro?</h3>
        <div class="text-muted">¿Realmente deseas anular esta venta? Esta acción no se puede deshacer de forma sencilla.</div>
      </div>
      <div class="modal-footer">
        <div class="w-100">
          <div class="row">
            <div class="col">
                <a href="#" class="btn w-100" data-bs-dismiss="modal">
                    Cancelar
                </a>
            </div>
            <div class="col">
                <input type="hidden" id="annul_sale_id">
                <button type="button" class="btn btn-danger w-100" id="btn-confirm-annul">
                    Sí, anular
                </button>
            </div>
          </div>
        </div>
      </div>
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
        $('#annul_sale_id').val(id);
        $('#confirmAnnulModal').modal('show');
	});

    $('#btn-confirm-annul').click(function(){
        var id = $('#annul_sale_id').val();
        var btn = $(this);
        btn.prop('disabled', true).addClass('btn-loading');

        $.ajax({
            url: '{{ route('sales.index') }}' + '/' + id,
            method: 'DELETE',
            success: function(data){
                if(data.status){
                    location.reload();
                }else{
                    btn.prop('disabled', false).removeClass('btn-loading');
                    $('#confirmAnnulModal').modal('hide');
                    alert(data.error ? data.error : 'La venta no se pudo anular.');
                }
            },
            error: function(err){
                btn.prop('disabled', false).removeClass('btn-loading');
                console.log(err);
            }
        });
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
		var type = $(this).data('type');

		$('#dispatch_sale_id').val(id);
		$('#dispatch_guide').text(guide);
		$('#dispatch_total').text(total);
		$('#dispatchForm')[0].reset();
		$('#dispatchPaymentContainer').hide();
		$('#payment-rows-container').empty();
		$('#total-distributed').text('S/0.00');
		$('#payment-warning').hide();

		if(type === 'Credito'){
			$('#payment-status-wrapper').hide();
			$('#dispatch_paid_no').prop('checked', true); // Default to unpaid for Credit
		} else {
			$('#payment-status-wrapper').show();
		}

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


</script>
@endsection

