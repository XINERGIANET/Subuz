@extends('template.app')

@section('title', 'Ventas')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Ventas</li>
  </ol>
</nav>
<style>
    .image-zoom-container {
        position: relative;
        cursor: zoom-in !important;
        display: inline-block !important;
        border-radius: 12px;
        overflow: hidden;
        line-height: 0;
        border: 3px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .image-zoom-container:hover {
        border-color: #244BB3 !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .image-zoom-container img {
        transition: transform 0.15s ease-out;
        transform-origin: center center;
        display: block;
        width: 100%;
        height: auto;
    }
    .image-zoom-container:hover img {
        transform: scale(2.5) !important;
    }
</style>
<script>
    console.log("Sales view loaded");
</script>
<div class="card">
	<div class="card-header d-flex justify-content-between flex-column flex-sm-row gap-2">
		<div>
			@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller') || auth()->user()->hasRole('asistente'))
			<a class="btn btn-brand" href="{{ route('sales.create') }}">
				<i class="ti ti-plus icon"></i> Crear nuevo
			</a>
			<a class="btn btn-success" href="{{ route('sales.excel', request()->query()) }}">
				<i class="ti ti-download icon"></i> Excel
			</a>
			<button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modal-sales-report">
				<i class="ti ti-file-text icon"></i> Reportes
			</button>
			@endif
			@if(auth()->user()->hasRole('admin'))
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
		@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('asistente') || auth()->user()->hasRole('seller'))
		<div class="d-flex text-center gap-3 flex-wrap justify-content-center justify-content-md-end">
			<div>
				<span class="d-block small text-muted">Total Entregado {{ auth()->user()->hasRole('asistente') ? '(Hoy)' : '' }}</span>
				<span class="fs-3 fw-bold text-primary">S/{{ number_format($total_sales, 2) }}</span>
			</div>
			<div class="vr mx-1"></div>
			@foreach($payment_totals as $pt)
			<div>
				<span class="d-block small text-muted">{{ $pt->name == 'Interbank' ? 'Interbank Empresa' : $pt->name }} {{ auth()->user()->hasRole('asistente') ? '(Hoy)' : '' }}</span>
				<span class="fs-3 fw-bold text-success">S/{{ number_format($pt->total, 2) }}</span>
			</div>
			<div class="vr mx-1"></div>
			@endforeach

			<div>
				<span class="d-block small text-muted">No Pagado {{ auth()->user()->hasRole('asistente') ? '(Hoy)' : '' }}</span>
				<span class="fs-3 fw-bold text-danger">S/{{ number_format($total_unpaid_delivered, 2) }}</span>
			</div>
			<div class="vr mx-1"></div>
			<div>
				<span class="d-block small text-muted">No Entregado {{ auth()->user()->hasRole('asistente') ? '(Hoy)' : '' }}</span>
				<span class="fs-3 fw-bold text-warning">S/{{ number_format($total_not_delivered, 2) }}</span>
			</div>
		</div>
		@endif

		@if(auth()->user()->hasRole('despachador'))
		<div class="d-flex text-center gap-4 flex-wrap justify-content-center justify-content-md-end">
			<div>
				<span class="d-block small text-muted">
					Pedidos Entregados
				</span>
				<span class="fs-2 fw-bold text-primary">
					{{ $delivered_count ?? 0 }}
				</span>
			</div>
			<div class="vr mx-2 d-none d-md-block"></div>
			@if(isset($payment_totals) && $payment_totals->count() > 0)
				@foreach($payment_totals as $pt)
				<div>
					<span class="d-block small text-muted">
						Total {{ $pt->name }}
					</span>
					<span class="fs-2 fw-bold text-success">
						S/{{ number_format($pt->total, 2) }}
					</span>
				</div>
				@endforeach
			@else
				<div>
					<span class="d-block small text-muted">Total Efectivo</span>
					<span class="fs-2 fw-bold text-success">S/0.00</span>
				</div>
			@endif
			<div class="vr mx-2 d-none d-md-block"></div>
			<div>
				<span class="d-block small text-muted">
					Valor Total Entregado
				</span>
				<span class="fs-2 fw-bold text-info">
					S/{{ number_format($total_sales, 2) }}
				</span>
			</div>
		</div>
		@endif
	</div>
	<div class="card-body border-bottom">
		<form class="mb-3">
			<div class="row">
				<div class="col-lg-2">
					<div class="mb-3">
						<label class="form-label">Cliente</label>
						<select class="form-select ts-clients" name="client_id">
							<option value="">Seleccionar</option>
							@if(isset($selected_client) && $selected_client)
								<option value="{{ $selected_client->id }}" selected>{{ $selected_client->name }}</option>
							@endif
						</select>
					</div>
				</div>
				@if(!auth()->user()->hasRole('despachador'))
					@php $is_admin = auth()->user()->hasRole('admin'); @endphp
					<div class="col-lg-2">
						<div class="mb-3">
							<label class="form-label">Tipo de venta</label>
							<select class="form-select" name="type">
								<option value="">Seleccionar</option>
								<option value="Contado" {{ request()->type == 'Contado' ? 'selected' : '' }}>Contado</option>
								<option value="Credito" {{ request()->type == 'Credito' ? 'selected' : '' }}>Crédito</option>
								<option value="Pago pendiente" {{ request()->type == 'Pago pendiente' ? 'selected' : '' }}>Pago pendiente</option>
							</select>
						</div>
					</div>
					<div class="col-lg-2">
						<div class="mb-3">
							<label class="form-label">Cuenta</label>
							<select class="form-select" name="payment_method_id">
								<option value="">Seleccionar</option>
								<option value="credit" {{ request()->payment_method_id == 'credit' ? 'selected' : '' }}>Crédito</option>
								@foreach($payment_methods as $pm)
									<option value="{{ $pm->id }}" {{ request()->payment_method_id == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
								@endforeach
							</select>
						</div>
					</div>
					<div class="col-lg-2">
						<div class="mb-3">
							<label class="form-label">Estado de entrega</label>
							<select class="form-select" name="delivery_status">
								<option value="">Seleccionar</option>
								<option value="delivered" {{ request()->delivery_status == 'delivered' ? 'selected' : '' }}>Entregado</option>
								<option value="pending" {{ request()->delivery_status == 'pending' ? 'selected' : '' }}>No entregado</option>
							</select>
						</div>
					</div>
					@if($is_admin)
						<div class="col-lg-2">
							<div class="mb-3">
								<label class="form-label">Fecha desde</label>
								<input type="date" class="form-control" name="start_date" value="{{ request()->start_date ? request()->start_date : now()->format('Y-m-d') }}">
							</div>
						</div>
						<div class="col-lg-2">
							<div class="mb-3">
								<label class="form-label">Fecha hasta</label>
								<input type="date" class="form-control" name="end_date" value="{{ request()->end_date ? request()->end_date : now()->format('Y-m-d') }}">
							</div>
						</div>
					@endif
				@endif
			</div>
			<div class="mt-3 d-flex justify-content-between align-items-center">
				<div>
					<button type="submit" class="btn btn-brand"><i class="ti ti-filter icon"></i> Filtrar</button>
					<a href="{{ route('sales.index') }}" class="btn btn-outline-secondary ms-2"><i class="ti ti-rotate-clockwise icon"></i> Limpiar filtros</a>
				</div>
				@if(isset($total_by_type) && $total_by_type !== null)
				<div class="text-end d-flex gap-2 flex-wrap justify-content-end">
					<div class="p-2 px-3 border rounded-2 bg-azure-lt shadow-sm">
						<span class="text-azure small text-uppercase fw-bold">Total {{ request('type') }} Entregado:</span>
						<span class="fs-2 fw-bold text-azure ms-2">S/{{ number_format($total_by_type_delivered, 2) }}</span>
					</div>
					<div class="p-2 px-3 border rounded-2 bg-warning-lt shadow-sm">
						<span class="text-warning small text-uppercase fw-bold">Total {{ request('type') }} No Entregado:</span>
						<span class="fs-2 fw-bold text-warning ms-2">S/{{ number_format($total_by_type_not_delivered, 2) }}</span>
					</div>
				</div>
				@endif
			</div>
		</form>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>#</th>
					<th>Guía de remisión</th>
					<th>Fecha</th>
					<th>Tipo de venta</th>
					<th>Método de pago</th>
					<th>Cliente</th>
					<th>Estado</th>
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
					<td>
						@if($sale->payments->count() > 0)
							{{ $sale->payments->map(function($payment) { return optional($payment->payment_method)->name; })->filter()->unique()->implode(', ') }}
						@else
							{{ $sale->payment_method ? optional($sale->payment_method)->name : 'N/A' }}
						@endif
					</td>
					<td>{{ optional($sale->client)->name }}</td>
					<td>
						@php
							$isDelivered = $sale->paid || $sale->type == 'Pago pendiente' || $sale->movements->where('type', 'debt')->isNotEmpty();
						@endphp
						@if($isDelivered)
						<span class="badge bg-success-lt">Entregado</span>
						@else
						<span class="badge bg-warning-lt">No entregado</span>
						@endif
					</td>
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
							<button class="btn btn-icon btn-show" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Detalle venta">
								<i class="ti ti-eye icon"></i>
							</button>
							@if((auth()->user()->hasRole('despachador') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('asistente'))  && !$isDelivered)
							<button class="btn btn-icon btn-dispatch" data-id="{{ $sale->id }}" data-order="{{ $sale->order }}" data-guide="{{ $sale->guide }}" data-total="{{ $sale->total }}" data-type="{{ $sale->type }}" data-bs-toggle="tooltip" title="Despachar">
								<i class="ti ti-check icon"></i>
							</button>
							@endif
							@if((auth()->user()->hasRole('despachador') || auth()->user()->hasRole('admin') || auth()->user()->hasRole('asistente'))  && $isDelivered)
							<button class="btn btn-icon btn-reopen-delivery" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Volver a no entregado">
								<i class="ti ti-rotate-2 icon"></i>
							</button>
							@endif
							@if(auth()->user()->hasRole('admin') || auth()->user()->hasRole('seller') || auth()->user()->hasRole('asistente'))
							<button class="btn btn-icon btn-edit-corporate btn-edit" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Editar">
								<i class="ti ti-edit icon"></i>
							</button>
							<button class="btn btn-icon btn-delete-corporate btn-delete" data-id="{{ $sale->id }}" data-bs-toggle="tooltip" title="Eliminar">
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
  		  <div class="mb-3 d-none" id="photo-container">
  		    <label class="form-label">Foto de evidencia</label>
   		    <div class="text-center">
   		      <div class="image-zoom-container">
   		        <img src="" id="show-photo" class="img-fluid" style="max-height: 400px;">
   		      </div>
   		    </div>
  		  </div>
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
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-bold">Total:</td>
                    <td class="fw-bold fs-4 text-brand" id="modal-sale-total">S/0.00</td>
                </tr>
            </tfoot>
  		  </table>

  		</div>
        <div class="modal-footer bg-light-subtle">
            <button type="button" class="btn btn-brand w-100" onclick="location.reload()">Aceptar</button>
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
			<div class="row">
				<div class="col-md-6 mb-4">
					<label class="form-label">Fecha</label>
					<input type="date" class="form-control" id="date">
				</div>
				<div class="col-md-6 mb-4">
					<label class="form-label">Tipo de Venta</label>
					<select class="form-select" id="edit_type">
						<option value="Contado">Contado</option>
						<option value="Credito">Crédito</option>
						<option value="Pago pendiente">Pago pendiente</option>
					</select>
				</div>
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
			<tfoot>
				<tr>
					<td colspan="3" class="text-end fw-bold">Total recalculado:</td>
					<td class="fw-bold fs-4 text-brand" id="edit-modal-total">S/0.00</td>
				</tr>
			</tfoot>
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
			@csrf
  			<div class="modal-header">
  			  <h5 class="modal-title">Confirmar pago</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  				<div class="mb-3 p-3 bg-light rounded border">
  					<div class="small text-uppercase fw-bold text-muted mb-1">Detalle de Venta</div>
  					<div class="h3 mb-0">
  						Venta: <span id="dispatch_guide" class="text-primary"></span>
  					</div>
  				</div>
   				<div class="mb-3">
  					<label class="form-label fw-bold">Productos de la Venta</label>
					<div class="table-responsive mb-2">
						<table class="table table-sm table-vcenter">
							<thead class="small text-muted">
								<tr>
									<th>Producto</th>
									<th>Precio</th>
									<th style="width: 80px">Cant.</th>
									<th>Subtotal</th>
									<th></th>
								</tr>
							</thead>
							<tbody id="tbl-dispatch-items"></tbody>
							<tfoot>
								<tr>
									<td colspan="3" class="text-end fw-bold">Total Venta:</td>
									<td class="fw-bold text-brand h4 mb-0">S/<span id="dispatch_total"></span></td>
									<td></td>
								</tr>
							</tfoot>
						</table>
					</div>

					<div class="p-2 bg-light rounded border mb-3">
						<div class="row g-2">
							<div class="col-7">
								<select class="form-select form-select-sm ts-products-dispatch" id="add-dispatch-product-id">
									<option value="">Añadir producto...</option>
									@foreach($products as $product)
									<option value="{{ $product->id }}">{{ $product->name }} - S/{{ $product->price }}</option>
									@endforeach
								</select>
							</div>
							<div class="col-3">
								<input type="number" class="form-control form-control-sm" id="add-dispatch-quantity" value="1" min="1">
							</div>
							<div class="col-2">
								<button type="button" class="btn btn-sm btn-brand w-100" id="btn-add-dispatch-detail">
									<i class="ti ti-plus"></i>
								</button>
							</div>
						</div>
					</div>

  					<label class="form-label fw-bold">Detalles del Despacho</label>
                    <div class="row g-2">
                        <div class="col-md-12 mb-2">
                            <label class="form-label small text-muted mb-1">N&uacute;mero de Gu&iacute;a</label>
                            <input type="text" name="guide" id="dispatch_guide_input" class="form-control" placeholder="Ej: GR-00001" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small text-muted mb-1">Foto de Evidencia</label>
                            <input type="file" name="photo" id="dispatch_photo" class="form-control" accept="image/*" capture="camera" required>
                            <div id="photo_preview_container" class="mt-2 text-center d-none">
                                <img id="photo_preview" src="#" alt="Vista previa" class="img-fluid rounded border shadow-sm" style="max-height: 200px;">
                            </div>
                        </div>
                    </div>
  				</div>

  				<div id="payment-question-container">
					<div class="mb-3">
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
				</div>

				<div id="credit-message-container" style="display:none">
					<div class="alert alert-info d-flex align-items-center gap-2 py-2">
						<i class="ti ti-info-circle fs-2"></i>
						<div>Esta venta es a <strong>Crédito</strong>. Solo confirme la entrega.</div>
					</div>
                    <input type="hidden" name="is_credit_hidden" id="is_credit_hidden" value="0">
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
@endsection

@section('modal')
<div class="modal modal-blur fade" id="modal-sales-report" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="ti ti-report-analytics me-2"></i>Reporte General de Ventas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light-lt">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Desde</label>
                        <input type="date" id="report-start-date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Hasta</label>
                        <input type="date" id="report-end-date" class="form-control" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger w-100" id="btn-refresh-report">
                            <i class="ti ti-refresh icon"></i>
                        </button>
                    </div>
                </div>

                <div id="report-content">
                    <!-- Summary Cards -->
                    <div class="row g-2 mb-3" id="report-summary-container">
                        <!-- Dynamic summary -->
                    </div>

                    <div class="card border-0 shadow-sm p-3 mb-3">
                        <h4 class="fw-bold text-danger border-bottom pb-2 mb-3">Ingresos por Método (Efectivo, Yape, etc)</h4>
                        <div class="row g-2" id="report-methods-container">
                            <!-- Dynamic methods -->
                        </div>
                    </div>
                </div>

                <div id="report-loader" class="text-center py-5 d-none">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="mt-2 text-muted">Generando previsualización...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btn-download-report-pdf" class="btn btn-danger px-4 shadow-sm">
                    <i class="ti ti-file-type-pdf icon me-1"></i> Descargar PDF
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
	$(document).on('click', '[data-bs-target="#modal-sales-report"]', function() {
		loadSalesReportData();
	});

	$('#btn-refresh-report').on('click', function() {
		loadSalesReportData();
	});

	function loadSalesReportData() {
		const start = $('#report-start-date').val();
		const end = $('#report-end-date').val();

		$('#btn-download-report-pdf').attr('href', `{{ route('sales.report_pdf') }}?start_date=${start}&end_date=${end}`);

		$('#report-content').addClass('d-none');
		$('#report-loader').removeClass('d-none');

		$.ajax({
			url: `{{ route('sales.report_data') }}`,
			method: 'GET',
			data: { start_date: start, end_date: end },
			success: function(response) {
				renderSalesReport(response);
				$('#report-loader').addClass('d-none');
				$('#report-content').removeClass('d-none');
			},
			error: function() {
				alert('Error al cargar los datos del reporte');
				$('#report-loader').addClass('d-none');
			}
		});
	}

	function renderSalesReport(data) {
		// Render Summary Cards (Top row)
		let summaryHtml = `
			<div class="col-md-3">
				<div class="card p-3 border-0 shadow-sm text-center bg-white">
					<div class="small fw-bold text-muted text-uppercase mb-1">Total Entregado</div>
					<div class="h3 mb-0 fw-bold text-primary">S/ ${data.summary.total_delivered}</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card p-3 border-0 shadow-sm text-center bg-white">
					<div class="small fw-bold text-muted text-uppercase mb-1">No Pagado</div>
					<div class="h3 mb-0 fw-bold text-danger">S/ ${data.summary.total_unpaid_delivered}</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card p-3 border-0 shadow-sm text-center bg-white">
					<div class="small fw-bold text-muted text-uppercase mb-1">No Entregado</div>
					<div class="h3 mb-0 fw-bold text-warning">S/ ${data.summary.total_not_delivered}</div>
				</div>
			</div>
			<div class="col-md-3">
				<div class="card p-3 border-0 shadow-sm text-center bg-white">
					<div class="small fw-bold text-muted text-uppercase mb-1">Total Gastos</div>
					<div class="h3 mb-0 fw-bold text-danger">S/ ${data.summary.total_expenses}</div>
				</div>
			</div>
		`;
		$('#report-summary-container').html(summaryHtml);

		// Render Methods and Previous Payments
		let methodsHtml = '';
		const methods = data.summary.methods;
		if (Object.keys(methods).length > 0) {
			Object.keys(methods).forEach(method => {
				methodsHtml += `
					<div class="col-6 col-md-3">
						<div class="p-2 border rounded bg-light">
							<div class="small text-muted text-uppercase">${method}</div>
							<div class="fw-bold text-success">S/ ${parseFloat(methods[method]).toFixed(2)}</div>
						</div>
					</div>
				`;
			});
		} else {
			methodsHtml = '<div class="col-12 text-center text-muted small py-2">No se registraron cobros en este periodo</div>';
		}

		// Add Previous Payments Info
		if (data.summary.previous_payments_count > 0) {
			methodsHtml += `
				<div class="col-12 mt-2">
					<div class="alert alert-info d-flex align-items-center mb-0 py-2">
						<i class="ti ti-info-circle me-2 fs-2"></i>
						<span>Se han procesado <strong>${data.summary.previous_payments_count}</strong> pagos correspondientes a ventas de días anteriores.</span>
					</div>
				</div>
			`;
		}

		$('#report-methods-container').html(methodsHtml);
	}
</script>
<script>
	$(document).ready(function () {
		$('.ts-clients').each(function(){
			if (this.tomselect) return;
            
            var initialOptions = [];
            var initialValue = '';
            
            @if(isset($selected_client) && $selected_client)
                initialOptions = [{
                    id: '{{ $selected_client->id }}',
                    name: '{{ addslashes($selected_client->name) }}',
                    business_name: '{{ addslashes($selected_client->business_name) }}'
                }];
                initialValue = '{{ $selected_client->id }}';
            @endif

			new TomSelect(this, {
				valueField: 'id',
				labelField: 'name',
				searchField: ['name', 'business_name', 'document'],
				maxOptions: 50,
				preload: true,
                options: initialOptions,
                items: [initialValue],
				load: function(query, callback){
					var url = '{{ url('clients/api') }}?q=' + encodeURIComponent(query);
					fetch(url)
						.then(response => response.json())
						.then(json => {
							callback(json.items);
						}).catch(()=>{
							callback();
						});
				},
				render: {
					option: function(data, escape) {
						var dname = data.name || 'Sin Nombre';
						var bname = data.business_name ? `<small class="text-muted d-block">${escape(data.business_name)}</small>` : '';
						return `<div>${escape(dname)} ${bname}</div>`;
					},
					item: function(data, escape) {
						return `<div>${escape(data.name || 'Sin Nombre')}</div>`;
					}
				}
			});
		});
	});

	var tsProductsDetail = null;

	$(document).on('click', '.btn-show', function(){
		var id = $(this).data('id');
		$('#add-detail-product-id').val('').trigger('change'); // Reset selector if exists
		if(tsProductsDetail) tsProductsDetail.clear();

		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + id + '/details',
			method: 'GET',
			success: function(data){
				if(data.status){
					$('#showModal').attr('data-sale-id', id); // Store sale ID in modal
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
					$('#modal-sale-total').text('S/' + data.total);

					if(data.photo && data.photo.indexOf('photo-view/') !== -1 && !data.photo.endsWith('photo-view/')){
						$('#show-photo').attr('src', data.photo);
						$('#photo-container').removeClass('d-none');
					}else{
						$('#photo-container').addClass('d-none');
						$('#show-photo').attr('src', '');
					}

					$('#showModal').modal('show');
				}
			},
			error: function(err){
				console.log(err);
			}
		});

	});



	$(document).on('mousemove', '.image-zoom-container', function(e) {
		console.log('Zoom move detected');
		const rect = this.getBoundingClientRect();
		const x = e.clientX - rect.left;
		const y = e.clientY - rect.top;
		
		const width = rect.width;
		const height = rect.height;
		
		const xPercent = (x / width) * 100;
		const yPercent = (y / height) * 100;
		
		$(this).find('img').css('transform-origin', `${xPercent}% ${yPercent}%`);
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
					var modalTotal = 0;

					$('#sale_id').val(data.id);
					$('#date').val(data.date);
					$('#edit_type').val(data.type);

					data.details.forEach(function(item){
						var subtotal = (Number(item.price)*Number(item.quantity));
						modalTotal += subtotal;
						html += `
							<tr class="edit-item-row">
								<td><input type="hidden" name="detail_id[]" value="${item.id}"> ${item.product.name}</td>
								<td><input type="number" step="0.01" class="form-control form-control-sm edit-price" name="price[]" value="${item.price}" style="width: 100px"></td>
								<td><input type="number" step="1" class="form-control form-control-sm edit-qty" name="quantity[]" value="${item.quantity}" style="width: 100px"></td>
								<td class="edit-subtotal">S/${subtotal.toFixed(2)}</td>
							</tr>
						`;
					});

					$('#tbl-edit-items').html(html);
					$('#edit-modal-total').text('S/' + modalTotal.toFixed(2));

					$('#editModal').modal('show');
				}
			},
			error: function(err){
				console.log(err);
			}
		});

	});

	$(document).on('input', '.edit-price, .edit-qty', function() {
		var total = 0;
		$('#tbl-edit-items .edit-item-row').each(function() {
			var price = parseFloat($(this).find('.edit-price').val()) || 0;
			var qty = parseInt($(this).find('.edit-qty').val()) || 0;
			var subtotal = price * qty;
			$(this).find('.edit-subtotal').text('S/' + subtotal.toFixed(2));
			total += subtotal;
		});
		$('#edit-modal-total').text('S/' + total.toFixed(2));
	});

	$('#btn-save').click(function(){

		var checkTotal = $('#edit-modal-total').text();
		
		Swal.fire({
			title: '¿Confirmar cambios?',
			text: "Esta venta se actualizará con un nuevo total de " + checkTotal + ".",
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#206bc4', // brand color approximation
			cancelButtonColor: '#d33',
			confirmButtonText: 'Sí, guardar',
			cancelButtonText: 'Cancelar'
		}).then((result) => {
			if (result.isConfirmed) {
				var id = $('#sale_id').val();

				var data = {
					date: null,
					type: null,
					details: {
						id: [],
						price: [],
						quantity: []
					}
				};

				data.date = $('#date').val();
				data.type = $('#edit_type').val();

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
							ToastError.fire({ text: data.error });
						}
					}
				});
			}
		});

	});

	$(document).on('click', '.btn-delete', function(){
		var id = $(this).data('id');
        var isAdmin = @json(auth()->user()->hasRole('admin'));
        var message = isAdmin 
            ? '¿Estás seguro que deseas ELIMINAR esta venta? Esta acción borrará permanentemente el registro y sus pagos, y restaurará el stock.' 
            : '¿Estás seguro que deseas ANULAR esta venta?';

		if(confirm(message)){
			$.ajax({
				url: '{{ route('sales.index') }}' + '/' + id,
				method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
				success: function(data){
					if(data.status){
						location.reload();
					}else{
						alert(data.error || 'Ocurrió un error al procesar la solicitud.');
					}
				},
				error: function(err){
					console.log(err);
					var msg = 'No se pudo eliminar/anular la venta.';
					if(err.responseJSON && err.responseJSON.error){
						msg = err.responseJSON.error;
					}
					alert(msg);
				}
			});
		}
	});

	var paymentMethodsHtml = `
		@foreach($payment_methods as $pm)
			<option value="{{ $pm->id }}">{{ $pm->name }}</option>
		@endforeach
	`;

	var tsProductsDispatch = null;

	$(document).on('click', '.btn-dispatch', function(){
		$('#dispatchForm')[0].reset();
		var id = $(this).data('id');
		var order = $(this).data('order');
		var guide = $(this).data('guide');
		var type = $(this).data('type');

		$('#dispatch_sale_id').val(id);
		$('#dispatch_guide').text(order);
		$('#dispatch_guide_input').val(guide);

		if(type == 'Credito'){
			$('#payment-question-container').hide();
			$('#credit-message-container').show();
			$('#dispatch_paid_no').prop('checked', true);
			$('#is_credit_hidden').val('1');
		}else{
			$('#payment-question-container').show();
			$('#credit-message-container').hide();
			$('#is_credit_hidden').val('0');
		}

		$('#dispatchPaymentContainer').hide();
		$('#payment-rows-container').empty();
		setPaymentInputsState(false);
		$('#payment-warning').hide();
    $('#photo_preview_container').addClass('d-none');
    $('#photo_preview').attr('src', '#');

		loadDispatchDetails(id);

		$('#dispatchModal').modal('show');

		if($('#add-dispatch-product-id').length > 0 && !tsProductsDispatch){
			tsProductsDispatch = new TomSelect('#add-dispatch-product-id', {
				copyClassesToDropdown: false,
				dropdownClass: 'dropdown-menu ts-dropdown',
				optionClass:'dropdown-item',
				controlInput: '<input>',
				render:{
					no_results: function(data, escape){
						return '<div class="no-results">No se encontraron resultados</div>';
					}
				}
			});
		}
	});

	function loadDispatchDetails(saleId){
		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + saleId + '/details',
			method: 'GET',
			success: function(data){
				if(data.status){
					var html = '';
					data.details.forEach(function(item){
						var subtotal = (Number(item.price)*Number(item.quantity)).toFixed(2);
						html += `
							<tr>
								<td>${item.product.name}</td>
								<td>
									<input type="number" step="0.01" class="form-control form-control-sm edit-dispatch-price" data-id="${item.id}" value="${item.price}" style="width: 70px">
								</td>
								<td>
									<input type="number" step="1" class="form-control form-control-sm edit-dispatch-qty" data-id="${item.id}" value="${item.quantity}" style="width: 60px">
								</td>
								<td>S/${subtotal}</td>
								<td class="text-end">
									<button type="button" class="btn btn-sm btn-icon btn-ghost-danger btn-delete-dispatch-detail" data-id="${item.id}">
										<i class="ti ti-trash"></i>
									</button>
								</td>
							</tr>
						`;
					});
					$('#tbl-dispatch-items').html(html);
					$('#dispatch_total').text(data.total);
					calculateDistributed();
				}
			}
		});
	}

	$(document).on('click', '#btn-add-dispatch-detail', function(){
		var saleId = $('#dispatch_sale_id').val();
		var productId = $('#add-dispatch-product-id').val();
		var quantity = $('#add-dispatch-quantity').val();

		if(!productId || !quantity){
			ToastError.fire({ text: 'Seleccione producto y cantidad' });
			return;
		}

		$.ajax({
			url: '{{ url('sales') }}/' + saleId + '/add-detail',
			method: 'POST',
			data: { product_id: productId, quantity: quantity },
			success: function(data){
				if(data.status){
					loadDispatchDetails(saleId);
					if(tsProductsDispatch) tsProductsDispatch.clear();
					$('#add-dispatch-quantity').val(1);
				} else {
					ToastError.fire({ text: data.error });
				}
			}
		});
	});

	$(document).on('change', '.edit-dispatch-qty, .edit-dispatch-price', function(){
		var saleId = $('#dispatch_sale_id').val();
		var detailId = $(this).data('id');
		var row = $(this).closest('tr');
		var quantity = row.find('.edit-dispatch-qty').val();
		var price = row.find('.edit-dispatch-price').val();

		$.ajax({
			url: '{{ url('sales') }}/' + saleId + '/details/' + detailId,
			method: 'PATCH',
			data: { quantity: quantity, price: price },
			success: function(data){
				if(data.status){
					loadDispatchDetails(saleId);
				} else {
					ToastError.fire({ text: data.error });
				}
			}
		});
	});

	$(document).on('click', '.btn-delete-dispatch-detail', function(){
		var saleId = $('#dispatch_sale_id').val();
		var detailId = $(this).data('id');

		if(!confirm('¿Eliminar producto de la venta?')) return;

		$.ajax({
			url: '{{ url('sales') }}/' + saleId + '/details/' + detailId,
			method: 'DELETE',
			success: function(data){
				if(data.status){
					loadDispatchDetails(saleId);
				}
			}
		});
	});

    $(document).on('change', '#dispatch_photo', function() {
        const file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(event) {
                $('#photo_preview').attr('src', event.target.result);
                $('#photo_preview_container').removeClass('d-none');
            }
            reader.readAsDataURL(file);
        }
    });

	function addPaymentRow(amount = '') {
// ... existing addPaymentRow function ...
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
		setPaymentInputsState($('input[name="paid"]:checked').val() == '1');
	}

	function setPaymentInputsState(enabled) {
		$('#payment-rows-container')
			.find('select[name^="payments["], input[name^="payments["]')
			.each(function() {
				$(this).prop('disabled', !enabled);
				$(this).prop('required', !!enabled);
			});
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

	function loadImageFromFile(file){
		return new Promise(function(resolve, reject){
			try{
				var objectUrl = URL.createObjectURL(file);
				var img = new Image();
				img.onload = function(){
					URL.revokeObjectURL(objectUrl);
					resolve(img);
				};
				img.onerror = function(){
					URL.revokeObjectURL(objectUrl);
					reject(new Error('No se pudo leer la imagen.'));
				};
				img.src = objectUrl;
			}catch(e){
				reject(e);
			}
		});
	}

	function canvasToBlob(canvas, quality){
		return new Promise(function(resolve, reject){
			if(!canvas.toBlob){
				reject(new Error('toBlob no soportado'));
				return;
			}
			canvas.toBlob(function(blob){
				if(!blob){
					reject(new Error('No se pudo convertir imagen'));
					return;
				}
				resolve(blob);
			}, 'image/jpeg', quality);
		});
	}

	async function compressImageForUpload(file, maxBytes){
		if(!file || file.size <= maxBytes){
			return file;
		}

		var img = await loadImageFromFile(file);
		var w = img.naturalWidth || img.width;
		var h = img.naturalHeight || img.height;
		var canvas = document.createElement('canvas');
		var ctx = canvas.getContext('2d');
		if(!ctx){
			return file;
		}

		var currentW = w;
		var currentH = h;
		var quality = 0.9;
		var blob = null;
		var tries = 0;

		while(tries < 12){
			canvas.width = Math.max(1, Math.round(currentW));
			canvas.height = Math.max(1, Math.round(currentH));
			ctx.clearRect(0, 0, canvas.width, canvas.height);
			ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
			blob = await canvasToBlob(canvas, quality);
			if(blob.size <= maxBytes){
				break;
			}
			if(quality > 0.45){
				quality -= 0.1;
			}else{
				currentW *= 0.85;
				currentH *= 0.85;
			}
			tries++;
		}

		if(!blob){
			return file;
		}
		var finalName = (file.name || 'evidencia.jpg').replace(/\.[^.]+$/, '') + '.jpg';
		return new File([blob], finalName, { type: 'image/jpeg' });
	}

	$(document).on('change', 'input[name="paid"]', function(){
		if($(this).val() == '1'){
			$('#dispatchPaymentContainer').fadeIn();
			if($('#payment-rows-container').is(':empty')) {
				addPaymentRow($('#dispatch_total').text());
			}
			setPaymentInputsState(true);
		}else{
			$('#dispatchPaymentContainer').fadeOut();
			setPaymentInputsState(false);
			$('#payment-warning').hide();
			$('#total-distributed').removeClass('text-danger text-success').text('S/0.00');
		}
	});

	$('#dispatchForm').submit(async function(e){
		e.preventDefault();
		
		var isPaid = $('input[name="paid"]:checked').val() == '1';
		setPaymentInputsState(isPaid);
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
        var formData = new FormData(this);
		var photoInput = document.getElementById('dispatch_photo');
		if(photoInput && photoInput.files && photoInput.files[0]){
			try{
				// Comprime automáticamente para evitar 413 en móvil/hosting.
				var optimized = await compressImageForUpload(photoInput.files[0], 7 * 1024 * 1024);
				formData.set('photo', optimized, optimized.name);
			}catch(_e){
				// Si falla compresión, envía archivo original.
			}
		}

		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + id + '/dispatch',
			method: 'POST',
			data: formData,
            processData: false,
            contentType: false,
			success: function(data){
				if(data.status){
					$('#dispatchModal').modal('hide');
					location.reload();
				}else{
					ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error inesperado al procesar el despacho.' });
				}
			},
			error: function(err){
                var errMsg = 'Error de conexión con el servidor.';
                if(err.status === 413){
                    errMsg = 'La foto es demasiado pesada para el servidor (HTTP 413). Pruebe con una imagen más ligera.';
                }else if(err.status === 419){
                    errMsg = 'La sesión expiró (HTTP 419). Recargue la página e intente nuevamente.';
                }else if(err.responseJSON && err.responseJSON.error){
                    errMsg = err.responseJSON.error;
                }else if(err.status){
                    errMsg = 'Error HTTP ' + err.status + '.';
                }
				ToastError.fire({ text: errMsg });
			}
		});
	});

	$(document).on('change', '.select-delivery-status', function(){
		var id = $(this).data('id');
		var status = $(this).val();

        if (status == '1') {
            // Instead of direct AJAX, we open the modal
            var row = $(this).closest('tr');
            var guide = row.find('td:eq(1)').text().trim();
            var total = row.find('td:eq(7)').text().replace('S/', '').trim();
			var type = $(this).data('type');

            $('#dispatch_sale_id').val(id);
            $('#dispatch_guide').text(guide);
            $('#dispatch_guide_input').val(guide);
            $('#dispatchForm')[0].reset();

			if(type == 'Credito'){
				$('#payment-question-container').hide();
				$('#credit-message-container').show();
				$('#dispatch_paid_no').prop('checked', true);
				$('#is_credit_hidden').val('1');
			}else{
				$('#payment-question-container').show();
				$('#credit-message-container').hide();
				$('#is_credit_hidden').val('0');
			}

            $('#dispatchPaymentContainer').hide();
            $('#payment-rows-container').empty();
            setPaymentInputsState(false);
            $('#payment-warning').hide();
            $('#photo_preview_container').addClass('d-none');
            $('#photo_preview').attr('src', '#');

						loadDispatchDetails(id);

            $('#dispatchModal').modal('show');
            
            // Revert the select in case they cancel the modal
            $(this).val('0');

						if($('#add-dispatch-product-id').length > 0 && !tsProductsDispatch){
							tsProductsDispatch = new TomSelect('#add-dispatch-product-id', {
								copyClassesToDropdown: false,
								dropdownClass: 'dropdown-menu ts-dropdown',
								optionClass:'dropdown-item',
								controlInput: '<input>',
								render:{
									no_results: function(data, escape){
										return '<div class="no-results">No se encontraron resultados</div>';
									}
								}
							});
						}
        } else {
            // Revert delivery (return to Credito)
            $.ajax({
                url: '{{ route('sales.index') }}' + '/' + id + '/delivery-status',
                method: 'POST',
                data: {
                    status: status
                },
                success: function(data){
                    if(data.status){
                        ToastMessage.fire({ text: 'Estado actualizado' })
                            .then(() => location.reload());
                    }else{
                        ToastError.fire({ text: data.error ? data.error : 'Ocurrió un error' });
                        location.reload();
                    }
                },
                error: function(err){
                    ToastError.fire({ text: 'Ocurrió un error al actualizar el estado' });
                    location.reload();
                }
            });
        }
	});

	$(document).on('click', '.btn-delete-detail', function(){
		// Method kept for other potential uses or empty
	});

	$(document).on('click', '.btn-reopen-delivery', function(){
		var id = $(this).data('id');
		if(!confirm('¿Volver esta venta a "No entregado"? Se quitarán pagos/movimientos del despacho y podrás subir una nueva foto.')) return;

		$.ajax({
			url: '{{ route('sales.index') }}' + '/' + id + '/delivery-status',
			method: 'POST',
			data: { status: 0 },
			success: function(data){
				if(data.status){
					ToastMessage.fire({ text: 'Venta revertida a No entregado' })
						.then(() => location.reload());
				}else{
					ToastError.fire({ text: data.error ? data.error : 'No se pudo revertir la venta.' });
				}
			},
			error: function(err){
				var msg = 'Error al revertir la venta.';
				if(err.responseJSON && err.responseJSON.error) msg = err.responseJSON.error;
				ToastError.fire({ text: msg });
			}
		});
	});

</script>
@endsection

