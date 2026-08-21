@extends('template.app')

@section('title', 'Caja')

@section('content')
<nav class="mb-3">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Caja</li>
  </ol>
</nav>

<div class="row row-cards mb-4">
    <div class="col-md-3">
        <div class="card metric-card border-0 shadow-sm overflow-hidden btn-method-breakdown" style="cursor: pointer;" data-id="1" data-name="Efectivo en Caja" title="Click para auditar/desglosar saldo">
            <div class="card-status-start bg-success"></div>
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-success-lt text-success avatar avatar-md">
                            <i class="ti ti-cash fs-2"></i>
                        </div>
                    </div>
                    <div class="col text-truncate">
                        <div class="text-uppercase text-muted small fw-bold d-flex justify-content-between align-items-center">
                            <span>Efectivo en Caja</span>
                            <i class="ti ti-lock text-muted fs-4"></i>
                        </div>
                        <div class="h2 mb-0 fw-bold text-success">S/{{ number_format($balances[1] ?? 0, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @foreach($payment_methods as $pm)
        @if($pm->id != 1 && ($balances[$pm->id] > 0 || (isset($cashbox->opening_balances[$pm->id]) && $cashbox->opening_balances[$pm->id] > 0)))
        <div class="col-md-3">
            <div class="card metric-card border-0 shadow-sm overflow-hidden btn-method-breakdown" style="cursor: pointer;" data-id="{{ $pm->id }}" data-name="{{ $pm->name }}" title="Click para auditar/desglosar saldo">
                <div class="card-status-start bg-azure"></div>
                <div class="card-body p-3">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <div class="bg-azure-lt text-azure avatar avatar-md">
                                <i class="ti ti-credit-card fs-2"></i>
                            </div>
                        </div>
                        <div class="col text-truncate">
                            <div class="text-uppercase text-muted small fw-bold d-flex justify-content-between align-items-center">
                                <span>{{ $pm->name }}</span>
                                <i class="ti ti-lock text-muted fs-4"></i>
                            </div>
                            <div class="h2 mb-0 fw-bold text-azure">S/{{ number_format($balances[$pm->id], 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach
    <div class="col-md-3">
        <div class="card border-0 shadow-sm overflow-hidden h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-uppercase text-muted small fw-bold">Estado Actual</div>
                    <div class="mt-1">
                        @if($cashbox)
                        <span class="badge bg-success-lt fw-bold px-2 py-1"><i class="ti ti-circle-check me-1"></i> ABIERTA</span>
                        @else
                        <span class="badge bg-danger-lt fw-bold px-2 py-1"><i class="ti ti-circle-x me-1"></i> CERRADA</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex flex-column gap-2">
                    @if($cashbox)
                    <button class="btn btn-primary btn-sm w-100 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#incomeModal">
                        <i class="ti ti-plus me-1 fs-3"></i> Nuevo Ingreso
                    </button>
                    <button class="btn btn-outline-primary btn-sm w-100 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#transferModal">
                        <i class="ti ti-arrows-transfer-down me-1 fs-3"></i> Transferir
                    </button>
                    <button class="btn btn-outline-danger btn-sm w-100 fw-bold btn-print-cashbox-pdf" data-id="{{ $cashbox->id }}" data-note="{{ $cashbox->note ?? '' }}" title="Imprimir Reporte Resumen">
                        <i class="ti ti-file-type-pdf me-1 fs-3"></i> Reporte PDF
                    </button>
                    <button class="btn btn-outline-secondary btn-sm w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#closeModal">
                        <i class="ti ti-door-exit me-1 fs-3"></i> Cerrar Caja
                    </button>
                    @else
                    <button class="btn btn-brand btn-sm px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#openModal">
                        <i class="ti ti-door-enter me-1 fs-3"></i> Aperturar Caja
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @if($cashbox)
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-2">
                <div class="row g-2 text-center">
                    <div class="col-3 border-end">
                        <div class="text-uppercase text-muted extra-small fw-bold">Inicial</div>
                        <div class="fw-bold text-dark">S/{{ number_format($cashbox->opening_amount, 2) }}</div>
                    </div>
                    <div class="col-3 border-end">
                        <div class="text-uppercase text-success extra-small fw-bold">Ventas/Pagos</div>
                        <div class="fw-bold text-success">S/{{ number_format($total_paid, 2) }}</div>
                    </div>
                    <div class="col-3 border-end">
                        <div class="text-uppercase text-azure extra-small fw-bold">Ingreso de Caja</div>
                        <div class="fw-bold text-azure">S/{{ number_format($total_manual_income, 2) }}</div>
                    </div>
                    <div class="col-3">
                        <div class="text-uppercase text-danger extra-small fw-bold">Egresos</div>
                        <div class="fw-bold text-danger">S/{{ number_format($total_expenses, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@if($cashbox)
<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header border-0 py-3 d-flex justify-content-between align-items-center">
        <h3 class="card-title fw-bold"><i class="ti ti-list me-2"></i>Movimientos de Hoy</h3>
        <span class="text-muted small">Abierta el: <strong>{{ $cashbox->opened_at->format('d/m/Y H:i') }}</strong></span>
    </div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>Fecha/Hora</th>
					<th>Referencia</th>
					<th>Cliente / Descripción</th>
					<th class="text-center">Estado</th>
					<th>Método</th>
					<th class="text-center">Monto</th>
					<th class="text-end">Usuario</th>
					@if(auth()->user()->hasRole('admin'))
					<th class="text-end">Acciones</th>
					@endif
				</tr>
			</thead>
			<tbody>
				@forelse($movements as $movement)
				<tr>
					<td class="small text-muted">{{ $movement->date->format('d/m/Y H:i') }}</td>
					<td class="fw-bold text-dark">{{ $movement->sale ? $movement->sale->guide : ($movement->type == 'expense' ? 'Egreso' : ($movement->type == 'transfer' ? 'Transferencia' : 'Ingreso de Caja')) }}</td>
					<td>
						@if($movement->type == 'income' || $movement->type == 'expense' || $movement->type == 'transfer')
							<span class="text-muted italic">{{ $movement->note }}</span>
						@else
							<span class="fw-medium text-dark">{{ optional(optional($movement->sale)->client)->name ?? 'Consumidor Final' }}</span>
						@endif
					</td>
					<td class="text-center">
						@if($movement->type == 'paid' || $movement->type == 'income')
						    <span class="badge bg-success-lt px-2 py-1">Pagado</span>
						@elseif($movement->type == 'expense')
						    <span class="badge bg-red-lt px-2 py-1">Gasto</span>
                        @elseif($movement->type == 'transfer')
						    <span class="badge bg-azure-lt px-2 py-1">Transferencia</span>
						@else
						    <span class="badge bg-warning-lt px-2 py-1">Deuda</span>
						@endif
					</td>
					<td>
                        <span class="badge bg-blue-lt fw-normal">
                            {{ $movement->payment_method ? $movement->payment_method->name : 'N/A' }}
                        </span>
                    </td>
					<td class="text-center fw-bold @if($movement->type=='expense' || ($movement->type=='transfer' && $movement->amount < 0)) text-danger @else text-primary @endif">
                        S/{{ number_format($movement->amount, 2) }}
                    </td>
					<td class="text-end small">{{ $movement->user ? explode(' ', $movement->user->name)[0] : 'N/A' }}</td>
					@if(auth()->user()->hasRole('admin'))
					<td class="text-end">
						@if($movement->id)
						<form method="POST" action="{{ route('cashbox.movements.destroy', $movement->id) }}" onsubmit="return confirm('¿Eliminar este movimiento de caja?');">
							@csrf
							@method('DELETE')
							<button type="submit" class="btn btn-icon btn-outline-danger btn-sm" title="Eliminar movimiento">
								<i class="ti ti-trash"></i>
							</button>
						</form>
						@endif
					</td>
					@endif
				</tr>
                @empty
				<tr>
					<td colspan="8" align="center" class="py-5 text-muted">
                        <i class="ti ti-mood-empty fs-1 mb-2 d-block"></i>
                        No hay movimientos registrados en la sesión actual
                    </td>
				</tr>
				@endforelse
			</tbody>
		</table>
	</div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-body py-5 text-center">
        <div class="avatar avatar-xl bg-red-lt mb-3">
            <i class="ti ti-lock fs-2"></i>
        </div>
        <h2 class="fw-bold">Caja cerrada</h2>
        <p class="text-muted mx-auto" style="max-width: 400px;">Para comenzar a registrar ventas y movimientos, debes realizar la apertura de la caja con un monto inicial.</p>
        <button class="btn btn-brand btn-pill px-4 mt-2" data-bs-toggle="modal" data-bs-target="#openModal">
            <i class="ti ti-door-enter me-2"></i> Aperturar Caja Ahora
        </button>
    </div>
</div>
@endif

<div class="modal modal-blur fade" id="incomeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form method="POST" action="{{ route('cashbox.income') }}">
  			@csrf
  			<div class="modal-header">
  			  <h5 class="modal-title">Registrar Ingreso de Caja</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  				<div class="mb-3">
  					<label class="form-label">Descripción / Motivo</label>
  					<input type="text" class="form-control" name="note" placeholder="Ej. Ingreso por cambio" required>
  				</div>
  				<div id="payment_rows">
  					<div class="row payment-row mb-3">
  						<div class="col-lg-6">
  							<label class="form-label">Monto</label>
  							<input type="number" step="0.01" class="form-control" name="amounts[]" required>
  						</div>
  						<div class="col-lg-5">
  							<label class="form-label">Método de pago</label>
  							<select class="form-select" name="payment_method_ids[]" required>
  								<option value="">Seleccionar</option>
  								@foreach($payment_methods as $pm)
  								<option value="{{ $pm->id }}">{{ $pm->name }}</option>
  								@endforeach
  							</select>
  						</div>
  						<div class="col-lg-1 d-flex align-items-end">
  							<button type="button" class="btn btn-icon btn-outline-danger remove-payment-row" style="display: none;">
  								<i class="ti ti-trash"></i>
  							</button>
  						</div>
  					</div>
  				</div>
  				<button type="button" class="btn btn-outline-primary btn-sm mt-2" id="add_payment_row">
  					<i class="ti ti-plus"></i> Agregar otro método
  				</button>
  			</div>
  			<div class="modal-footer">
  				<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  				<button type="submit" class="btn btn-brand"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
  	</div>
  </div>
</div>

<div class="modal modal-blur fade" id="transferModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form method="POST" action="{{ route('cashbox.transfer') }}">
  			@csrf
  			<div class="modal-header">
  			  <h5 class="modal-title">Transferencia entre Cuentas</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  				<div class="mb-3">
  					<label class="form-label">Desde (Origen)</label>
                    <select class="form-select" name="from_payment_method_id" required>
                        <option value="">Seleccionar cuenta origen</option>
                        @foreach($payment_methods as $pm)
                        <option value="{{ $pm->id }}">{{ $pm->name }} (S/{{ number_format($balances[$pm->id] ?? 0, 2) }})</option>
                        @endforeach
                    </select>
  				</div>
                <div class="mb-3 text-center">
                    <i class="ti ti-arrow-down fs-1 text-muted"></i>
                </div>
                <div class="mb-3">
  					<label class="form-label">Hacia (Destino)</label>
                    <select class="form-select" name="to_payment_method_id" required>
                        <option value="">Seleccionar cuenta destino</option>
                        @foreach($payment_methods as $pm)
                        <option value="{{ $pm->id }}">{{ $pm->name }} (S/{{ number_format($balances[$pm->id] ?? 0, 2) }})</option>
                        @endforeach
                    </select>
  				</div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Monto a transferir</label>
                            <div class="input-group">
                                <span class="input-group-text">S/</span>
                                <input type="number" step="0.01" class="form-control" name="amount" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">Nota / Referencia</label>
                            <input type="text" class="form-control" name="note" placeholder="Ej. Depósito banco">
                        </div>
                    </div>
                </div>
  			</div>
  			<div class="modal-footer">
  				<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  				<button type="submit" class="btn btn-brand"><i class="ti ti-arrows-exchange icon"></i> Transferir</button>
  			</div>
  		</form>
  	</div>
  </div>
</div>

<div class="modal modal-blur fade" id="openModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form method="POST" action="{{ route('cashbox.open') }}">
  			@csrf
  			<div class="modal-header">
  			  <h5 class="modal-title">Aperturar caja</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
   			<div class="modal-body">
   				<div class="mb-3">
   					<label class="form-label fw-bold"><i class="ti ti-cash me-1"></i>Efectivo (Monto inicial)</label>
   					<input type="number" step="0.01" class="form-control" name="opening_amount" value="{{ $suggested_opening_amount }}" placeholder="0.00">
   				</div>
                <hr class="my-3">
                <div class="text-uppercase text-muted small fw-bold mb-3">Otros métodos de pago</div>
                @foreach($payment_methods as $pm)
                    @if($pm->id != 1) {{-- Saltamos efectivo porque ya está arriba --}}
                    <div class="mb-3">
                        <label class="form-label">{{ $pm->name }}</label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" step="0.01" class="form-control" name="opening_balances[{{ $pm->id }}]" value="{{ isset($suggested_opening_balances[$pm->id]) ? number_format($suggested_opening_balances[$pm->id], 2, '.', '') : '0.00' }}">
                        </div>
                    </div>
                    @endif
                @endforeach
   			</div>
  			<div class="modal-footer">
  				<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  				<button type="submit" class="btn btn-brand"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
    </div>
  </div>
</div>

<div class="modal modal-blur fade" id="closeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form id="closeCashboxForm" method="POST" action="{{ route('cashbox.close') }}">
  			@csrf
  			<div class="modal-header">
  			  <h5 class="modal-title">Cerrar caja</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  				<div class="mb-3">
  					<label class="form-label fw-bold">Efectivo (Monto de cierre)</label>
  					<input type="number" step="0.01" class="form-control" name="closing_amount" value="{{ $balances[1] ?? 0 }}">
  					<small class="text-muted">Sugerido: apertura + ventas - gastos</small>
  				</div>
                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="text-uppercase text-muted extra-small fw-bold mb-2">Otros saldos sugeridos</div>
                    @foreach($payment_methods as $pm)
                        @if($pm->id != 1 && (isset($balances[$pm->id]) && $balances[$pm->id] > 0))
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small">{{ $pm->name }}:</span>
                            <span class="small fw-bold text-azure">S/{{ number_format($balances[$pm->id], 2) }}</span>
                        </div>
                        @endif
                    @endforeach
                </div>
  				<div class="mb-3">
  					<label class="form-label">Observación</label>
  					<textarea class="form-control" name="note" rows="3" placeholder="Opcional..."></textarea>
  				</div>
  			</div>
  			<div class="modal-footer">
  				<button type="button" class="btn me-auto" data-bs-dismiss="modal"><i class="ti ti-x icon"></i> Cerrar</button>
  				<button type="submit" class="btn btn-delete-corporate"><i class="ti ti-device-floppy icon"></i> Guardar</button>
  			</div>
  		</form>
    </div>
  </div>
</div>
<!-- Modal Desglose y Auditoría de Cuenta / Banco -->
<div class="modal modal-blur fade" id="methodBreakdownModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
  	<div class="modal-content">
  		<div class="modal-header bg-light py-3">
  		  <div>
  		      <span class="badge bg-primary text-white mb-1"><i class="ti ti-lock me-1"></i> Auditoría de Saldo</span>
  		      <h4 class="modal-title fw-bold text-dark mb-0" id="breakdown-method-title">Desglose de Cuenta</h4>
  		  </div>
  		  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  		</div>
  		<div class="modal-body p-4">
  		    <div class="row g-3 mb-4">
  		        <div class="col-md-6">
  		            <div class="card p-3 border shadow-sm">
  		                <div class="d-flex justify-content-between align-items-center mb-1">
  		                    <span class="text-uppercase text-muted extra-small fw-bold">Saldo Inicial (Apertura)</span>
  		                    <button class="btn btn-outline-primary btn-sm py-0 px-2" id="btn-edit-opening" title="Ajustar saldo inicial">
  		                        <i class="ti ti-pencil me-1"></i> Modificar
  		                    </button>
  		                </div>
  		                <div class="h2 mb-0 fw-bold text-dark" id="breakdown-opening-display">S/ 0.00</div>
  		                <div id="opening-edit-form" class="mt-2 d-none">
  		                    <div class="input-group input-group-sm">
  		                        <span class="input-group-text">S/</span>
  		                        <input type="number" step="0.01" class="form-control" id="opening_amount_input">
  		                        <button class="btn btn-primary" type="button" id="btn-save-opening">Guardar</button>
  		                        <button class="btn btn-secondary" type="button" id="btn-cancel-opening">Cancelar</button>
  		                    </div>
  		                </div>
  		            </div>
  		        </div>
  		        <div class="col-md-6">
  		            <div class="card p-3 border shadow-sm bg-primary-lt">
  		                <span class="text-uppercase text-primary extra-small fw-bold mb-1">Saldo Actual Calculado</span>
  		                <div class="h2 mb-0 fw-bold text-primary" id="breakdown-current-display">S/ 0.00</div>
  		            </div>
  		        </div>
  		    </div>

  		    <h6 class="fw-bold text-uppercase text-muted mb-2"><i class="ti ti-list me-2"></i>Movimientos de esta sesión</h6>
  		    <div class="table-responsive border rounded mb-0" style="max-height: 350px; overflow-y: auto;">
  		        <table class="table table-vcenter table-hover mb-0">
  		            <thead class="table-light sticky-top">
  		                <tr>
  		                    <th>Fecha/Hora</th>
  		                    <th>Tipo</th>
  		                    <th>Ref / Guía</th>
  		                    <th>Cliente / Nota</th>
  		                    <th class="text-center">Monto</th>
  		                    <th class="text-end">Acción</th>
  		                </tr>
  		            </thead>
  		            <tbody id="breakdown-movements-body">
  		                <!-- Dynamic lines -->
  		            </tbody>
  		        </table>
  		    </div>
  		</div>
  		<div class="modal-footer">
  			<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
  		</div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
	var activeMethodId = null;

	$(document).ready(function(){
		$('#add_payment_row').click(function(){
			let newRow = $('.payment-row').first().clone();
			newRow.find('input').val('');
			newRow.find('select').val('');
			newRow.find('.remove-payment-row').show();
			$('#payment_rows').append(newRow);
		});

		$(document).on('click', '.remove-payment-row', function(){
			$(this).closest('.payment-row').remove();
		});

		$('#incomeModal form, #openModal form').on('submit', function(){
			$(this).find('button[type="submit"]').prop('disabled', true);
		});

        // Permitir cerrar caja aunque el efectivo sea negativo, con confirmación.
        $('#closeCashboxForm').on('submit', function(e){
            var $form = $(this);
            var raw = $form.find('input[name="closing_amount"]').val();
            var amount = parseFloat(raw);

            if(!isNaN(amount) && amount < 0){
                e.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Efectivo de cierre en negativo',
                    text: 'El efectivo de cierre es S/ ' + amount.toFixed(2) + '. ¿Deseas cerrar la caja de todas formas?',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cerrar',
                    cancelButtonText: 'Cancelar',
                }).then(function(result){
                    if(result.isConfirmed){
                        $form.off('submit');
                        $form.trigger('submit');
                    }
                });
            }
        });

        // Auditoría con Clave de Seguridad
        $(document).on('click', '.btn-method-breakdown', function(){
            var methodId = $(this).data('id');
            var methodName = $(this).data('name');

            Swal.fire({
                title: 'Seguridad de Caja',
                text: 'Ingresa la clave de autorización para ver y editar los movimientos de ' + methodName,
                input: 'password',
                inputPlaceholder: 'Ingresa la clave...',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-key me-1"></i> Acceder',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#206bc4',
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-secondary px-4'
                },
                buttonsStyling: false,
                inputValidator: (value) => {
                    if (!value) {
                        return 'Debes ingresar una clave';
                    }
                    if (value !== 'xinergia123') {
                        return 'Clave incorrecta. Acceso denegado.';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    activeMethodId = methodId;
                    loadMethodBreakdown(methodId);
                }
            });
        });

        function loadMethodBreakdown(methodId) {
            Swal.fire({
                title: 'Cargando desglose...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: '{{ url("cashbox/method-breakdown") }}/' + methodId,
                method: 'GET',
                success: function(data){
                    Swal.close();
                    if(data.status){
                        $('#breakdown-method-title').text('Desglose de ' + data.payment_method.name);
                        $('#breakdown-opening-display').text('S/ ' + parseFloat(data.opening_amount).toFixed(2));
                        $('#opening_amount_input').val(parseFloat(data.opening_amount).toFixed(2));
                        $('#breakdown-current-display').text('S/ ' + parseFloat(data.current_balance).toFixed(2));
                        $('#opening-edit-form').addClass('d-none');
                        $('#breakdown-opening-display').removeClass('d-none');

                        var html = '';
                        var allItems = [];

                        if(data.movements){
                            data.movements.forEach(function(m){
                                allItems.push(m);
                            });
                        }
                        if(data.expenses){
                            data.expenses.forEach(function(e){
                                allItems.push(e);
                            });
                        }

                        if(allItems.length === 0){
                            html = '<tr><td colspan="6" class="text-center py-4 text-muted">No hay movimientos registrados para esta cuenta en la sesión actual.</td></tr>';
                        } else {
                            allItems.forEach(function(item){
                                var isExpense = item.type === 'expense' || (item.type === 'transfer' && item.amount < 0);
                                var typeLabel = item.type === 'paid' ? '<span class="badge bg-success-lt">Pago</span>' : 
                                               (item.type === 'income' ? '<span class="badge bg-success-lt">Ingreso</span>' : 
                                               (item.type === 'expense' ? '<span class="badge bg-red-lt">Gasto</span>' : 
                                               (item.type === 'transfer' ? '<span class="badge bg-azure-lt">Transferencia</span>' : '<span class="badge bg-warning-lt">Deuda</span>')));

                                var deleteBtn = '';
                                if(item.type !== 'expense' && item.id){
                                    deleteBtn = `<button class="btn btn-icon btn-outline-danger btn-sm btn-delete-box-item" data-id="${item.id}" title="Eliminar este movimiento">
                                                    <i class="ti ti-trash"></i>
                                                 </button>`;
                                } else {
                                    deleteBtn = '<span class="text-muted small">Desde Gastos</span>';
                                }

                                html += `
                                    <tr>
                                        <td class="small text-muted">${item.date}</td>
                                        <td>${typeLabel}</td>
                                        <td class="fw-bold">${item.reference}</td>
                                        <td class="small">${item.description}</td>
                                        <td class="text-center fw-bold ${isExpense ? 'text-danger' : 'text-primary'}">
                                            S/ ${parseFloat(item.amount).toFixed(2)}
                                        </td>
                                        <td class="text-end">${deleteBtn}</td>
                                    </tr>
                                `;
                            });
                        }

                        $('#breakdown-movements-body').html(html);
                        $('#methodBreakdownModal').modal('show');
                    } else {
                        ToastError.fire({ text: data.error });
                    }
                },
                error: function(err){
                    Swal.close();
                    ToastError.fire({ text: 'Error al consultar el desglose de la cuenta.' });
                }
            });
        }

        // Editar saldo inicial
        $('#btn-edit-opening').click(function(){
            $('#breakdown-opening-display').addClass('d-none');
            $('#opening-edit-form').removeClass('d-none');
        });

        $('#btn-cancel-opening').click(function(){
            $('#opening-edit-form').addClass('d-none');
            $('#breakdown-opening-display').removeClass('d-none');
        });

        $('#btn-save-opening').click(function(){
            var newAmount = parseFloat($('#opening_amount_input').val());
            if(isNaN(newAmount)){
                ToastError.fire({ text: 'Ingresa un monto válido.' });
                return;
            }

            $.ajax({
                url: '{{ url("cashbox/update-opening-balance") }}/' + activeMethodId,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    opening_amount: newAmount
                },
                success: function(data){
                    if(data.status){
                        ToastMessage.fire({ text: data.message }).then(() => {
                            location.reload();
                        });
                    } else {
                        ToastError.fire({ text: data.error });
                    }
                },
                error: function(err){
                    ToastError.fire({ text: 'Error al actualizar el saldo inicial.' });
                }
            });
        });

        // Eliminar movimiento individual desde el modal
        $(document).on('click', '.btn-delete-box-item', function(){
            var movementId = $(this).data('id');

            Swal.fire({
                title: '¿Eliminar este movimiento?',
                text: 'El monto se descontará inmediatamente del saldo de esta cuenta.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d63939',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'btn btn-danger px-4',
                    cancelButton: 'btn btn-secondary px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ url("cashbox/movements") }}/' + movementId,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(data){
                            if(data.status){
                                ToastMessage.fire({ text: 'Movimiento eliminado correctamente.' }).then(() => {
                                    location.reload();
                                });
                            } else {
                                ToastError.fire({ text: data.error || 'Error al eliminar.' });
                            }
                        },
                        error: function(err){
                            ToastError.fire({ text: 'Error en el servidor al eliminar el movimiento.' });
                        }
                    });
                }
            });
        });

        // Imprimir Reporte Resumen PDF de Caja
        $(document).on('click', '.btn-print-cashbox-pdf', function(e){
            e.preventDefault();
            var cashboxId = $(this).data('id');
            var defaultNote = $(this).data('note') || '';

            Swal.fire({
                title: 'Generar Reporte de Caja (PDF)',
                text: 'Puedes agregar o editar una observación final antes de imprimir:',
                input: 'textarea',
                inputValue: defaultNote,
                inputPlaceholder: 'Ej: Todo conforme, entrega de dinero a administración...',
                showCancelButton: true,
                confirmButtonText: '<i class="ti ti-printer me-1"></i> Generar PDF',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#206bc4',
                customClass: {
                    confirmButton: 'btn btn-primary px-4',
                    cancelButton: 'btn btn-secondary px-4'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var observation = result.value || '';
                    var url = '{{ url("reports/cashbox") }}/' + cashboxId + '/pdf?observation=' + encodeURIComponent(observation);
                    window.open(url, '_blank');
                }
            });
        });
	});
</script>
@endsection
