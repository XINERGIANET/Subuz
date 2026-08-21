@extends('template.app')

@section('title', 'Historial y Reporte de Cierres de Caja')

@section('content')
<div class="container-xl">
    <!-- Breadcrumb -->
    <nav class="mb-2">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
        <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reportes</a></li>
        <li class="breadcrumb-item active">Historial Cierres de Caja</li>
      </ol>
    </nav>

    <!-- Page Header -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title text-primary fw-bold">
                    <i class="ti ti-cash me-2"></i> Historial y Cuadre de Cajas (Día por Día)
                </h2>
                <div class="text-muted mt-1">
                    Auditoría de apertura, cierre, montos esperados y diferencias por sesión de caja
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form action="{{ route('reports.cashbox') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label for="start_date" class="form-label fw-bold text-muted small mb-1"><i class="ti ti-calendar me-1"></i>Fecha Desde</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="{{ $start_date }}">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label for="end_date" class="form-label fw-bold text-muted small mb-1"><i class="ti ti-calendar me-1"></i>Fecha Hasta</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="{{ $end_date }}">
                </div>
                <div class="col-md-6 col-12 d-flex align-items-end gap-1 mt-3 mt-md-0">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="ti ti-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('reports.cashbox', ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">
                        Hoy
                    </a>
                    <a href="{{ route('reports.cashbox', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-outline-secondary">
                        Este Mes
                    </a>
                    <a href="{{ route('reports.cashbox', ['start_date' => '2020-01-01', 'end_date' => now()->format('Y-m-d')]) }}" class="btn btn-sm btn-ghost-primary ms-auto">
                        Ver Todo el Historial
                    </a>
                </div>
            </form>
        </div>
    </div>

    @php
        $totalAperturas = $cashboxes->sum('opening_amount');
        $totalCierres = $cashboxes->where('is_open', 0)->sum('closing_amount');
        $totalDiferencias = $cashboxes->where('is_open', 0)->sum(function($cb) {
            $movs = $cb->movements;
            $total_paid = $movs->where('type', 'paid')->sum('amount');
            $total_income = $movs->where('type', 'income')->sum('amount');
            $total_expenses = $cb->expenses_list ? $cb->expenses_list->sum('amount') : 0;
            $expected = ($cb->opening_amount + $total_paid + $total_income) - $total_expenses;
            return floatval($cb->closing_amount) - $expected;
        });
    @endphp

    <!-- KPI Summary Cards -->
    <div class="row row-cards mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-blue text-white avatar">
                                <i class="ti ti-history icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">
                                {{ $cashboxes->count() }} Sesiones de Caja
                            </div>
                            <div class="text-muted small">
                                En el rango seleccionado
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-azure text-white avatar">
                                <i class="ti ti-door-open icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-azure fw-bold">
                                S/ {{ number_format($totalAperturas, 2) }}
                            </div>
                            <div class="text-muted small">
                                Total Montos Apertura
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-green text-white avatar">
                                <i class="ti ti-door-closed icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-success fw-bold">
                                S/ {{ number_format($totalCierres, 2) }}
                            </div>
                            <div class="text-muted small">
                                Total Montos Cierre Real
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm border-0 shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="{{ $totalDiferencias >= 0 ? 'bg-purple' : 'bg-red' }} text-white avatar">
                                <i class="ti ti-scale icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium {{ $totalDiferencias >= 0 ? 'text-purple' : 'text-danger' }} fw-bold">
                                {{ $totalDiferencias >= 0 ? '+' : '' }} S/ {{ number_format($totalDiferencias, 2) }}
                            </div>
                            <div class="text-muted small">
                                Balance Total de Cuadre
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Table: Cashbox History Day by Day -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="card-title fw-bold mb-0 text-primary">
                <i class="ti ti-table me-2"></i> Resumen de Cajas Día por Día / Sesión por Sesión
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter table-hover table-striped card-table mb-0">
                <thead>
                    <tr class="table-light">
                        <th class="text-muted text-uppercase small">Caja #</th>
                        <th class="text-muted text-uppercase small">Apertura (Fecha / Usuario)</th>
                        <th class="text-muted text-uppercase small">Cierre (Fecha / Usuario)</th>
                        <th class="text-muted text-uppercase small text-end">Monto Apertura</th>
                        <th class="text-muted text-uppercase small text-end">Saldo Esperado</th>
                        <th class="text-muted text-uppercase small text-end">Monto Cierre</th>
                        <th class="text-muted text-uppercase small text-end">Dif. vs Apertura</th>
                        <th class="text-muted text-uppercase small text-center">Cuadre vs Esperado</th>
                        <th class="text-muted text-uppercase small text-center">Estado</th>
                        <th class="text-muted text-uppercase small text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashboxes as $cb)
                        @php
                            $movs = $cb->movements;
                            $total_paid = $movs->where('type', 'paid')->sum('amount');
                            $total_income = $movs->where('type', 'income')->sum('amount');
                            $total_expenses = $cb->expenses_list ? $cb->expenses_list->sum('amount') : 0;
                            $expected = ($cb->opening_amount + $total_paid + $total_income) - $total_expenses;

                            $diff_opening = floatval($cb->closing_amount) - floatval($cb->opening_amount);
                            $cuadre = $cb->is_open ? 0 : floatval($cb->closing_amount) - $expected;
                        @endphp
                        <tr>
                            <td class="fw-bold">#{{ $cb->id }}</td>
                            <td>
                                <div><i class="ti ti-calendar me-1 text-muted"></i>{{ $cb->opened_at ? $cb->opened_at->format('d/m/Y H:i') : '-' }}</div>
                                <div class="small text-muted"><i class="ti ti-user me-1"></i>{{ optional($cb->openedBy)->name ?? 'Sistema' }}</div>
                            </td>
                            <td>
                                @if(!$cb->is_open)
                                    <div><i class="ti ti-calendar-check me-1 text-muted"></i>{{ $cb->closed_at ? $cb->closed_at->format('d/m/Y H:i') : '-' }}</div>
                                    <div class="small text-muted"><i class="ti ti-user me-1"></i>{{ optional($cb->closedBy)->name ?? 'Sistema' }}</div>
                                @else
                                    <span class="badge bg-success-lt fw-bold"><i class="ti ti-clock me-1"></i>En curso</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-dark">
                                S/ {{ number_format($cb->opening_amount, 2) }}
                            </td>
                            <td class="text-end fw-bold text-azure">
                                S/ {{ number_format($expected, 2) }}
                            </td>
                            <td class="text-end fw-bold text-success">
                                @if(!$cb->is_open)
                                    S/ {{ number_format($cb->closing_amount, 2) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold {{ $diff_opening >= 0 ? 'text-success' : 'text-danger' }}">
                                @if(!$cb->is_open)
                                    {{ $diff_opening >= 0 ? '+' : '' }} S/ {{ number_format($diff_opening, 2) }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$cb->is_open)
                                    @if(abs($cuadre) < 0.01)
                                        <span class="badge bg-success text-white fw-bold"><i class="ti ti-check me-1"></i>Cuadrado</span>
                                    @elseif($cuadre < 0)
                                        <span class="badge bg-danger text-white fw-bold" title="Faltante de caja"><i class="ti ti-alert-triangle me-1"></i>Faltante: S/ {{ number_format(abs($cuadre), 2) }}</span>
                                    @else
                                        <span class="badge bg-info text-white fw-bold" title="Sobrante de caja"><i class="ti ti-plus me-1"></i>Sobrante: +S/ {{ number_format($cuadre, 2) }}</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary-lt">Abierta</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $cb->is_open ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $cb->is_open ? 'ABIERTA' : 'CERRADA' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-detail-{{ $cb->id }}">
                                        <i class="ti ti-eye me-1"></i> Detalle
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-print-cashbox-pdf" data-id="{{ $cb->id }}" data-note="{{ $cb->note ?? '' }}" title="Imprimir Reporte PDF Resumen">
                                        <i class="ti ti-file-type-pdf me-1"></i> PDF
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="ti ti-info-circle me-1"></i> No se encontraron sesiones de caja en el rango de fechas seleccionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modals for Details of each Cashbox Session -->
    @foreach($cashboxes as $cb)
        @php
            $movs = $cb->movements;
            $total_paid = $movs->where('type', 'paid')->sum('amount');
            $total_income = $movs->where('type', 'income')->sum('amount');
            $total_expenses = $cb->expenses_list ? $cb->expenses_list->sum('amount') : 0;
            $expected = ($cb->opening_amount + $total_paid + $total_income) - $total_expenses;

            $opening_balances = is_string($cb->opening_balances) ? json_decode($cb->opening_balances, true) : $cb->opening_balances;
            $closing_balances = is_string($cb->closing_balances) ? json_decode($cb->closing_balances, true) : $cb->closing_balances;

            $display_list = $movs->map(function($m) use ($cb) {
                return (object)[
                    'date' => $m->date,
                    'type_label' => $m->type == 'paid' ? 'Venta' : ($m->type == 'income' ? 'Ingreso Manual' : 'Deuda'),
                    'type_class' => $m->type == 'paid' ? 'bg-green-lt' : ($m->type == 'income' ? 'bg-blue-lt' : 'bg-yellow-lt'),
                    'method' => optional($m->payment_method)->name ?? 'Sin método',
                    'amount' => $m->amount,
                    'amount_color' => 'text-primary',
                    'user' => optional($m->user)->name ?? (optional($cb->openedBy)->name ?? 'Sistema'),
                    'note' => $m->note
                ];
            });

            $expense_display = ($cb->expenses_list ?? collect())->map(function($e) use ($cb) {
                return (object)[
                    'date' => $e->date,
                    'type_label' => 'Egreso / Gasto',
                    'type_class' => 'bg-red-lt',
                    'method' => optional($e->payment_method)->name ?? 'Sin método',
                    'amount' => $e->amount,
                    'amount_color' => 'text-danger',
                    'user' => optional($e->user)->name ?? (optional($cb->openedBy)->name ?? 'Sistema'),
                    'note' => $e->description
                ];
            });

            $combined = $display_list->concat($expense_display)->sortByDesc('date');
        @endphp

        <!-- Modal Detalle por Caja -->
        <div class="modal modal-blur fade" id="modal-detail-{{ $cb->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <div>
                            <span class="badge {{ $cb->is_open ? 'bg-success' : 'bg-secondary' }} mb-1">
                                {{ $cb->is_open ? 'ABIERTA' : 'CERRADA' }}
                            </span>
                            <h5 class="modal-title fw-bold mb-0">Detalle de Sesión de Caja #{{ $cb->id }}</h5>
                            <small class="text-muted">Apertura: {{ $cb->opened_at ? $cb->opened_at->format('d/m/Y H:i') : '-' }} por {{ optional($cb->openedBy)->name ?? 'Sistema' }}</small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-4 mb-4">
                            <!-- Apertura por Medios de Pago -->
                            <div class="col-md-3">
                                <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="ti ti-door-open me-2"></i>Apertura por Método</h6>
                                @if($opening_balances && is_array($opening_balances))
                                    <ul class="list-unstyled mb-0">
                                    @foreach($opening_balances as $pm_id => $amount)
                                        @php 
                                            $pm_name = collect($payment_methods)->where('id', $pm_id)->first()->name ?? 'Desconocido';
                                        @endphp
                                        <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                            <span class="text-secondary">{{ $pm_name }}</span>
                                            <span class="fw-bold text-dark">S/ {{ number_format($amount, 2) }}</span>
                                        </li>
                                    @endforeach
                                    </ul>
                                @else
                                    <p class="text-muted small">Sin desglose registrado.</p>
                                @endif
                                <div class="d-flex justify-content-between mt-3 pt-2 bg-light p-2 rounded">
                                    <strong class="text-dark small text-uppercase">TOTAL APERTURA</strong>
                                    <strong class="text-primary">S/ {{ number_format($cb->opening_amount, 2) }}</strong>
                                </div>
                            </div>

                            <!-- Cierre por Medios de Pago -->
                            <div class="col-md-3 border-start">
                                <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="ti ti-door-closed me-2"></i>Cierre por Método</h6>
                                @if($closing_balances && is_array($closing_balances))
                                    <ul class="list-unstyled mb-0">
                                    @foreach($closing_balances as $pm_id => $amount)
                                        @php 
                                            $pm_name = collect($payment_methods)->where('id', $pm_id)->first()->name ?? 'Desconocido';
                                        @endphp
                                        <li class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                            <span class="text-secondary">{{ $pm_name }}</span>
                                            <span class="fw-bold text-dark">S/ {{ number_format($amount, 2) }}</span>
                                        </li>
                                    @endforeach
                                    </ul>
                                    <div class="d-flex justify-content-between mt-3 pt-2 bg-light p-2 rounded">
                                        <strong class="text-dark small text-uppercase">TOTAL CIERRE</strong>
                                        <strong class="text-primary">S/ {{ number_format($cb->closing_amount, 2) }}</strong>
                                    </div>
                                @else
                                    <div class="alert alert-light border text-center py-3 mb-0">
                                        <i class="ti ti-clock text-muted mb-1 fs-3"></i>
                                        <p class="mb-0 text-muted small fw-bold">CAJA ABIERTA EN CURSO</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Resumen Financiero del Turno -->
                            <div class="col-md-6 border-start">
                                <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="ti ti-chart-pie me-2"></i>Resumen del Turno</h6>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded border-start border-success border-4 d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="d-block text-muted small fw-bold text-uppercase mb-1">Total Ingresos</span>
                                                <h4 class="text-success mb-0 fw-bold">+ S/ {{ number_format($total_paid + $total_income, 2) }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded border-start border-danger border-4 d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="d-block text-muted small fw-bold text-uppercase mb-1">Total Egresos</span>
                                                <h4 class="text-danger mb-0 fw-bold">- S/ {{ number_format($total_expenses, 2) }}</h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 bg-light rounded border d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted d-block small text-uppercase fw-bold mb-1">Saldo Esperado en Caja</span>
                                        <h3 class="mb-0 text-dark fw-bold">S/ {{ number_format($expected, 2) }}</h3>
                                    </div>
                                    @if(!$cb->is_open)
                                        @php $cDiff = floatval($cb->closing_amount) - $expected; @endphp
                                        <div class="text-end">
                                            <span class="text-muted d-block small text-uppercase fw-bold mb-1">Diferencia de Cuadre</span>
                                            <h4 class="mb-0 fw-bold {{ abs($cDiff) < 0.01 ? 'text-success' : ($cDiff < 0 ? 'text-danger' : 'text-info') }}">
                                                {{ $cDiff >= 0 ? '+' : '' }} S/ {{ number_format($cDiff, 2) }}
                                            </h4>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($cb->note)
                            <div class="alert alert-warning mb-3 p-2 small">
                                <i class="ti ti-notes me-2"></i><strong>Nota de Cierre:</strong> {{ $cb->note }}
                            </div>
                        @endif

                        <h6 class="fw-bold text-uppercase text-muted mb-2"><i class="ti ti-receipt me-2"></i>Movimientos Registrados en el Turno</h6>
                        @if($combined->count() > 0)
                            <div class="table-responsive border rounded">
                                <table class="table table-vcenter table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 text-muted text-uppercase small">Hora</th>
                                            <th class="text-muted text-uppercase small">Tipo</th>
                                            <th class="text-muted text-uppercase small">Método</th>
                                            <th class="text-muted text-uppercase small">Monto</th>
                                            <th class="text-muted text-uppercase small">Usuario</th>
                                            <th class="pe-3 text-muted text-uppercase small">Nota / Descripción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($combined as $movement)
                                        <tr>
                                            <td class="ps-3 text-muted">{{ $movement->date ? \Carbon\Carbon::parse($movement->date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                            <td>
                                                <span class="badge {{ $movement->type_class }}">{{ $movement->type_label }}</span>
                                            </td>
                                            <td>{{ $movement->method }}</td>
                                            <td class="fw-bold {{ $movement->amount_color }}">S/ {{ number_format($movement->amount, 2) }}</td>
                                            <td>{{ $movement->user }}</td>
                                            <td class="pe-3 text-muted small">{{ $movement->note }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 bg-light rounded">
                                <i class="ti ti-receipt text-muted mb-1 fs-2"></i>
                                <p class="mb-0 text-muted small">No hay movimientos individuales registrados en esta sesión.</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light d-flex justify-content-between">
                        <button type="button" class="btn btn-danger btn-print-cashbox-pdf" data-id="{{ $cb->id }}" data-note="{{ $cb->note ?? '' }}">
                            <i class="ti ti-file-type-pdf me-1"></i> Imprimir Reporte Resumen (PDF)
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script>
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
</script>
@endsection
