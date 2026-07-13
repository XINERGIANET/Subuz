@extends('template.app')

@section('title', 'Despachadores')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item active">Despachadores</li>
  </ol>
</nav>

<div class="card">
	<div class="card-header d-flex flex-column flex-md-row justify-content-between gap-3">
		<form method="GET" action="{{ route('users.dispatchers.index') }}" class="flex-grow-1">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Desde</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->toDateString()) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Hasta</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-filter icon"></i> Filtrar
                    </button>
                    <a href="{{ route('users.dispatchers.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-refresh icon"></i>
                    </a>
                </div>
            </div>
        </form>
		<div class="align-self-end align-self-md-center">
            <a class="btn btn-brand" href="{{ route('users.dispatchers.create') }}">
                <i class="ti ti-plus icon"></i> Crear nuevo
            </a>
        </div>
	</div>
	<div class="table-responsive">
		<table class="table card-table table-vcenter">
			<thead class="table-corporate-header">
				<tr>
					<th>#</th>
					<th>Nombre</th>
					<th>Usuario</th>
					<th>Accion</th>
				</tr>
			</thead>
			<tbody>
				@if($dispatchers->count() > 0)
				@foreach($dispatchers as $dispatcher)
				<tr>
					<td>{{ $loop->iteration }}</td>
					<td>{{ $dispatcher->name }}</td>
					<td>{{ $dispatcher->user }}</td>
					<td>
						<div class="d-flex gap-2">
							<button class="btn btn-icon btn-outline-primary btn-preview" data-id="{{ $dispatcher->id }}" data-name="{{ $dispatcher->name }}" data-bs-toggle="tooltip" title="Previsualizar Reporte">
								<i class="ti ti-eye icon"></i>
							</button>
							<a class="btn btn-icon btn-outline-info" href="{{ route('users.dispatchers.report', array_merge(['dispatcher' => $dispatcher->id], request()->query())) }}" data-bs-toggle="tooltip" title="Descargar Reporte PDF">
								<i class="ti ti-file-text icon"></i>
							</a>
							<a class="btn btn-icon btn-edit-corporate" href="{{ route('users.dispatchers.edit', $dispatcher) }}" data-bs-toggle="tooltip" title="Editar">
								<i class="ti ti-edit icon"></i>
							</a>
							<form method="POST" action="{{ route('users.dispatchers.destroy', $dispatcher) }}" onsubmit="return confirm('Eliminar usuario?');">
								@csrf
								@method('DELETE')
								<button class="btn btn-icon btn-delete-corporate" type="submit" data-bs-toggle="tooltip" title="Eliminar">
									<i class="ti ti-trash icon"></i>
								</button>
							</form>
						</div>
					</td>
				</tr>
				@endforeach
				@else
				<tr>
					<td colspan="4" align="center">No se han encontrado registros</td>
				</tr>
				@endif
			</tbody>
		</table>
	</div>
	@if($dispatchers->hasPages())
	<div class="card-footer d-flex align-items-center">
		{{ $dispatchers->withQueryString()->links() }}
	</div>
	@endif
</div>
@endsection
 
@section('modal')
<div class="modal modal-blur fade" id="modal-report-preview" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-brand text-white">
                <h5 class="modal-title"><i class="ti ti-report-analytics me-2"></i>Previsualización de Reporte: <span id="modal-dispatcher-name"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light-lt">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Desde</label>
                        <input type="date" id="modal-start-date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted">Hasta</label>
                        <input type="date" id="modal-end-date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-brand w-100" id="btn-modal-refresh">
                            <i class="ti ti-refresh icon"></i>
                        </button>
                    </div>
                </div>

                <div id="modal-report-content">
                    <!-- Summary Cards -->
                    <div class="row g-2 mb-3" id="modal-summary-container">
                        <!-- Dynamic summary -->
                    </div>

                    <!-- Previous Payments Table (if any) -->
                    <div id="modal-previous-payments-container" class="card border-0 shadow-sm overflow-hidden mb-3 d-none">
                        <div class="card-header bg-green-lt py-2">
                            <h6 class="card-title mb-0 fw-bold text-success"><i class="ti ti-history me-1"></i> Pagos de Ventas Anteriores</h6>
                        </div>
                        <div class="table-responsive" style="max-height: 250px;">
                            <table class="table table-vcenter table-nowrap card-table bg-white">
                                <thead class="table-corporate-header sticky-top">
                                    <tr>
                                        <th>Guía</th>
                                        <th>Fecha/Hora</th>
                                        <th>Cliente</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-prev-table-body">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Details Table -->
                    <div class="card border-0 shadow-sm overflow-hidden">
                        <div class="card-header bg-brand-lt py-2">
                            <h6 class="card-title mb-0 fw-bold text-brand"><i class="ti ti-list me-1"></i> Entregas del Periodo</h6>
                        </div>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-vcenter table-nowrap card-table bg-white">
                                <thead class="table-corporate-header sticky-top">
                                    <tr>
                                        <th>Guía</th>
                                        <th>Fecha/Hora</th>
                                        <th>Cliente</th>
                                        <th class="text-center">Tipo</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-table-body">
                                    <!-- Dynamic rows -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Expenses Table -->
                    <div id="modal-expenses-container" class="card border-0 shadow-sm overflow-hidden mt-3 d-none">
                        <div class="card-header bg-danger-lt py-2">
                            <h6 class="card-title mb-0 fw-bold text-danger"><i class="ti ti-receipt me-1"></i> Gastos Registrados</h6>
                        </div>
                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-vcenter table-nowrap card-table bg-white">
                                <thead class="table-corporate-header sticky-top">
                                    <tr>
                                        <th>Fecha/Hora</th>
                                        <th>Descripción</th>
                                        <th>Categoría</th>
                                        <th class="text-center">Método</th>
                                        <th class="text-end">Monto</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-expenses-table-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="modal-loader" class="text-center py-5 d-none">
                    <div class="spinner-border text-brand" role="status"></div>
                    <p class="mt-2 text-muted">Cargando reporte...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cerrar</button>
                <a href="#" id="btn-modal-download-pdf" class="btn btn-brand px-4 shadow-sm">
                    <i class="ti ti-file-type-pdf icon me-1"></i> Descargar PDF
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let currentDispatcherId = null;

    $(document).on('click', '.btn-preview', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        currentDispatcherId = id;

        $('#modal-dispatcher-name').text(name);
        $('#modal-start-date').val($('input[name="start_date"]').val());
        $('#modal-end-date').val($('input[name="end_date"]').val());

        loadReportData();
        $('#modal-report-preview').modal('show');
    });

    $('#btn-modal-refresh').on('click', function() {
        loadReportData();
    });

    function loadReportData() {
        if (!currentDispatcherId) return;

        const start = $('#modal-start-date').val();
        const end = $('#modal-end-date').val();

        // Update PDF Download Link
        const pdfUrl = `{{ url('dispatchers') }}/${currentDispatcherId}/report?start_date=${start}&end_date=${end}`;
        $('#btn-modal-download-pdf').attr('href', pdfUrl);

        $('#modal-report-content').addClass('d-none');
        $('#modal-loader').removeClass('d-none');

        $.ajax({
            url: `{{ url('dispatchers') }}/${currentDispatcherId}/report-data`,
            method: 'GET',
            data: { start_date: start, end_date: end },
            success: function(response) {
                renderReport(response);
                $('#modal-loader').addClass('d-none');
                $('#modal-report-content').removeClass('d-none');
            },
            error: function() {
                alert('Error al cargar los datos del reporte');
                $('#modal-loader').addClass('d-none');
            }
        });
    }

    function renderReport(data) {
        // Render Summary
        let summaryHtml = '';
        
        // Methods breakdown
        Object.keys(data.summary.methods).forEach(method => {
            summaryHtml += `
                <div class="col-6 col-md-3">
                    <div class="card p-2 border-0 shadow-sm text-center">
                        <div class="small fw-bold text-muted text-uppercase mb-1">${method}</div>
                        <div class="h4 mb-0 fw-bold text-azure">S/ ${parseFloat(data.summary.methods[method]).toFixed(2)}</div>
                    </div>
                </div>
            `;
        });

        // Credit
        summaryHtml += `
            <div class="col-6 col-md-3">
                <div class="card p-2 border-0 shadow-sm text-center">
                    <div class="small fw-bold text-muted text-uppercase mb-1">Cŕedito</div>
                    <div class="h4 mb-0 fw-bold text-orange">S/ ${data.summary.credit}</div>
                </div>
            </div>
        `;

        // Total
        summaryHtml += `
            <div class="col-12 col-md-3">
                <div class="card p-2 border-0 shadow-sm text-center bg-brand-lt">
                    <div class="small fw-bold text-brand text-uppercase mb-1">Total Entregado</div>
                    <div class="h3 mb-0 fw-bold text-brand">S/ ${data.summary.total}</div>
                </div>
            </div>
        `;

        // Expenses Summary
        if (data.summary.expenses_total > 0) {
            summaryHtml += '<div class="col-12 mt-3 mb-1"><h6 class="text-danger fw-bold border-bottom pb-1 mb-0"><i class="ti ti-receipt me-1"></i> Resumen de Gastos</h6></div>';
            Object.keys(data.summary.expenses_methods).forEach(method => {
                summaryHtml += `
                    <div class="col-6 col-md-3">
                        <div class="card p-2 border-0 shadow-sm text-center border-bottom border-danger border-3">
                            <div class="small fw-bold text-muted text-uppercase mb-1">${method}</div>
                            <div class="h4 mb-0 fw-bold text-danger">S/ ${parseFloat(data.summary.expenses_methods[method]).toFixed(2)}</div>
                        </div>
                    </div>
                `;
            });

            summaryHtml += `
                <div class="col-12 col-md-3">
                    <div class="card p-2 border-0 shadow-sm text-center bg-danger-lt border-bottom border-danger border-3">
                        <div class="small fw-bold text-danger text-uppercase mb-1">Total Gastos</div>
                        <div class="h3 mb-0 fw-bold text-danger">S/ ${data.summary.expenses_total}</div>
                    </div>
                </div>
            `;
            summaryHtml += `
                <div class="col-12 mt-2">
                    <div class="card p-2 border-0 shadow-sm text-center bg-cyan-lt border-bottom border-cyan border-3">
                        <div class="small fw-bold text-cyan text-uppercase mb-1">Efectivo a Entregar (Cobranza - Gastos)</div>
                        <div class="h2 mb-0 fw-bold text-cyan">S/ ${data.summary.cash_handover}</div>
                    </div>
                </div>
            `;
        } else {
            summaryHtml += `
                <div class="col-12 mt-2">
                    <div class="card p-2 border-0 shadow-sm text-center bg-cyan-lt border-bottom border-cyan border-3">
                        <div class="small fw-bold text-cyan text-uppercase mb-1">Efectivo a Entregar (Solo Cobranza)</div>
                        <div class="h2 mb-0 fw-bold text-cyan">S/ ${data.summary.cash_handover}</div>
                    </div>
                </div>
            `;
        }

        $('#modal-summary-container').html(summaryHtml);

        // Render Previous Payments Table
        let prevTableHtml = '';
        if (data.previous_payments_count && data.previous_payments_count > 0) {
            data.previous_payments.forEach(mov => {
                const statusBadge = mov.payment_status === 'PAGADO' ? 'bg-success-lt' : 'bg-orange-lt';
                prevTableHtml += `
                    <tr>
                        <td class="fw-bold">${mov.guide}</td>
                        <td class="small">${mov.date}</td>
                        <td class="text-truncate" style="max-width: 150px;">${mov.client}</td>
                        <td class="text-center small">${mov.type}</td>
                        <td class="text-center">
                            <span class="badge ${statusBadge} px-2 py-1">${mov.payment_status}</span>
                        </td>
                        <td class="text-end fw-bold">S/ ${mov.amount}</td>
                    </tr>
                `;
            });
            $('#modal-prev-table-body').html(prevTableHtml);
            $('#modal-previous-payments-container').removeClass('d-none');
        } else {
            $('#modal-previous-payments-container').addClass('d-none');
        }

        // Render Table
        let tableHtml = '';
        if (data.movements.length > 0) {
            data.movements.forEach(mov => {
                const statusBadge = mov.payment_status === 'PAGADO' ? 'bg-success-lt' : 'bg-orange-lt';
                tableHtml += `
                    <tr>
                        <td class="fw-bold">${mov.guide}</td>
                        <td class="small">${mov.date}</td>
                        <td class="text-truncate" style="max-width: 150px;">${mov.client}</td>
                        <td class="text-center small">${mov.type}</td>
                        <td class="text-center">
                            <span class="badge ${statusBadge} px-2 py-1">${mov.payment_status}</span>
                        </td>
                        <td class="text-end fw-bold">S/ ${mov.amount}</td>
                    </tr>
                `;
            });
        } else {
            tableHtml = '<tr><td colspan="6" class="text-center py-4 text-muted small italic">No hay registros para este periodo</td></tr>';
        }
        $('#modal-table-body').html(tableHtml);

        // Render Expenses Table
        let expensesTableHtml = '';
        if (data.expenses && data.expenses.length > 0) {
            data.expenses.forEach(exp => {
                expensesTableHtml += `
                    <tr>
                        <td class="small">${exp.date}</td>
                        <td class="text-truncate" style="max-width: 150px;">${exp.description}</td>
                        <td>${exp.category}</td>
                        <td class="text-center small">${exp.payment_method}</td>
                        <td class="text-end fw-bold">S/ ${exp.amount}</td>
                    </tr>
                `;
            });
            $('#modal-expenses-table-body').html(expensesTableHtml);
            $('#modal-expenses-container').removeClass('d-none');
        } else {
            $('#modal-expenses-container').addClass('d-none');
        }
    }
</script>
@endsection
