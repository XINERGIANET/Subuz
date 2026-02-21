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
        <div class="card metric-card border-0 shadow-sm overflow-hidden">
            <div class="card-status-start bg-success"></div>
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <div class="bg-success-lt text-success avatar avatar-md">
                            <i class="ti ti-cash fs-2"></i>
                        </div>
                    </div>
                    <div class="col text-truncate">
                        <div class="text-uppercase text-muted small fw-bold">Efectivo en Caja</div>
                        <div class="h2 mb-0 fw-bold text-success">S/{{ number_format(($cashbox ? $cashbox->opening_amount + $total_paid + $total_manual_income - $total_expenses : 0), 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                    <button class="btn btn-outline-danger btn-sm w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#closeModal">
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
				</tr>
			</thead>
			<tbody>
				@forelse($movements as $movement)
				<tr>
					<td class="small text-muted">{{ $movement->date->format('d/m/Y H:i') }}</td>
					<td class="fw-bold text-dark">{{ optional($movement->sale)->guide ?? 'Ingreso de Caja' }}</td>
					<td>
						@if($movement->type == 'income')
							<span class="text-muted italic">{{ $movement->note }}</span>
						@else
							<span class="fw-medium text-dark">{{ optional(optional($movement->sale)->client)->name ?? 'Consumidor Final' }}</span>
						@endif
					</td>
					<td class="text-center">
						@if($movement->type == 'paid' || $movement->type == 'income')
						    <span class="badge bg-success-lt px-2 py-1">Pagado</span>
						@else
						    <span class="badge bg-warning-lt px-2 py-1">Deuda</span>
						@endif
					</td>
					<td>
                        <span class="badge bg-blue-lt fw-normal">
                            {{ $movement->payment_method ? $movement->payment_method->name : 'N/A' }}
                        </span>
                    </td>
					<td class="text-center fw-bold @if($movement->type=='expense') text-danger @else text-primary @endif">
                        S/{{ number_format($movement->amount, 2) }}
                    </td>
					<td class="text-end small">{{ $movement->user ? explode(' ', $movement->user->name)[0] : 'N/A' }}</td>
				</tr>
                @empty
				<tr>
					<td colspan="7" align="center" class="py-5 text-muted">
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
  					<label class="form-label">Monto inicial</label>
  					<input type="number" step="0.01" class="form-control" name="opening_amount">
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

<div class="modal modal-blur fade" id="closeModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
  	<div class="modal-content">
  		<form method="POST" action="{{ route('cashbox.close') }}">
  			@csrf
  			<div class="modal-header">
  			  <h5 class="modal-title">Cerrar caja</h5>
  			  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  			</div>
  			<div class="modal-body">
  				<div class="mb-3">
  					<label class="form-label">Monto de cierre</label>
  					<input type="number" step="0.01" class="form-control" name="closing_amount" value="{{ $suggested_closing_amount }}">
  					<small class="text-muted">Se calcula: apertura + ventas - gastos</small>
  				</div>
  				<div class="mb-3">
  					<label class="form-label">Observacion</label>
  					<textarea class="form-control" name="note" rows="3"></textarea>
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
@endsection

@section('scripts')
<script>
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
	});
</script>
@endsection
