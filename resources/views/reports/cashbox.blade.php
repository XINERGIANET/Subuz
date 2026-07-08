@extends('template.app')

@section('title', 'Reporte de Cierres de Caja')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reportes</a></li>
    <li class="breadcrumb-item active">Cierres de Caja</li>
  </ol>
</nav>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="{{ route('reports.cashbox') }}" method="GET" class="row align-items-end">
            <div class="col-md-4 col-sm-6">
                <label for="date" class="form-label fw-bold">Filtrar por Fecha</label>
                <input type="date" name="date" id="date" class="form-control" value="{{ $date }}">
            </div>
            <div class="col-md-2 col-sm-6 mt-3 mt-sm-0">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Buscar
                </button>
            </div>
        </form>
    </div>
</div>

<div class="alert alert-secondary shadow-sm mb-4 border-0" style="background-color: #f8f9fa;">
    <h5 class="alert-heading fw-bold text-dark"><i class="fas fa-info-circle me-2 text-primary"></i>Guía rápida de lectura</h5>
    <ul class="mb-0 text-muted" style="font-size: 0.9rem;">
        <li class="mb-1"><strong class="text-dark">Saldo Esperado en Caja:</strong> Es la sumatoria matemática automática que hace el sistema (Apertura + Ingresos - Egresos). Representa la cantidad total de dinero que debería haber juntando todos los medios de pago al finalizar el turno.</li>
        <li><strong class="text-dark">Nota de Cierre:</strong> Son los comentarios personales que el empleado/cajero redacta a mano justo en el instante en que cierra su turno (por ejemplo: justificar que falta dinero, o dinero que se fue a depositar al banco).</li>
    </ul>
</div>

@if($cashboxes->count() > 0)
    <div class="row">
        @foreach($cashboxes as $cb)
        <div class="col-12 mb-4">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge {{ $cb->is_open ? 'bg-success' : 'bg-secondary' }} mb-2">
                            {{ $cb->is_open ? 'ABIERTA' : 'CERRADA' }}
                        </span>
                        <h5 class="mb-0 fw-bold">Sesión de Caja #{{ $cb->id }}</h5>
                        <small class="text-muted">Apertura: {{ $cb->opened_at ? $cb->opened_at->format('d/m/Y H:i') : 'N/A' }} por {{ optional($cb->openedBy)->name ?? 'Sistema' }}</small>
                        @if(!$cb->is_open)
                            <br>
                            <small class="text-muted">Cierre: {{ $cb->closed_at ? $cb->closed_at->format('d/m/Y H:i') : 'N/A' }} por {{ optional($cb->closedBy)->name ?? 'Sistema' }}</small>
                        @endif
                    </div>
                    <div class="text-end">
                        <div class="text-muted small mb-1">Diferencia</div>
                        <h4 class="fw-bold {{ ($cb->closing_amount - $cb->opening_amount) >= 0 ? 'text-success' : 'text-danger' }}">
                            S/{{ number_format($cb->closing_amount - $cb->opening_amount, 2) }}
                        </h4>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4">
                        <!-- Apertura -->
                        <div class="col-md-3">
                            <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fas fa-door-open me-2"></i>Apertura</h6>
                            @php
                                $opening_balances = is_string($cb->opening_balances) ? json_decode($cb->opening_balances, true) : $cb->opening_balances;
                            @endphp
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
                                <p class="text-muted small">No hay detalle por método.</p>
                            @endif
                            <div class="d-flex justify-content-between mt-3 pt-2 bg-light p-2 rounded">
                                <strong class="text-dark small text-uppercase">TOTAL APERTURA</strong>
                                <strong class="text-primary">S/ {{ number_format($cb->opening_amount, 2) }}</strong>
                            </div>
                        </div>

                        <!-- Cierre -->
                        <div class="col-md-3 border-start">
                            <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fas fa-door-closed me-2"></i>Cierre</h6>
                            @php
                                $closing_balances = is_string($cb->closing_balances) ? json_decode($cb->closing_balances, true) : $cb->closing_balances;
                            @endphp
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
                                <div class="alert alert-light border text-center py-4 mb-0">
                                    <i class="fas fa-clock text-muted mb-2 fa-2x"></i>
                                    <p class="mb-0 text-muted small fw-bold">CAJA PENDIENTE DE CIERRE</p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Totales y Resumen -->
                        <div class="col-md-6 border-start">
                            <h6 class="fw-bold text-uppercase text-muted mb-3"><i class="fas fa-chart-pie me-2"></i>Movimientos del Turno</h6>
                            
                            @php
                                $movs = $cb->movements;
                                $total_paid = $movs->where('type', 'paid')->sum('amount');
                                $total_income = $movs->where('type', 'income')->sum('amount');
                                $total_expenses = $cb->expenses_list->sum('amount');
                                $expected = ($cb->opening_amount + $total_paid + $total_income) - $total_expenses;
                            @endphp

                            <div class="row mb-3">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <div class="p-3 bg-light rounded border-start border-success border-4 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="d-block text-muted small fw-bold text-uppercase mb-1">Total Ingresos</span>
                                            <h4 class="text-success mb-0 fw-bold">+ S/ {{ number_format($total_paid + $total_income, 2) }}</h4>
                                        </div>
                                        <i class="fas fa-arrow-up text-success opacity-50 fa-2x"></i>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 bg-light rounded border-start border-danger border-4 d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="d-block text-muted small fw-bold text-uppercase mb-1">Total Egresos</span>
                                            <h4 class="text-danger mb-0 fw-bold">- S/ {{ number_format($total_expenses, 2) }}</h4>
                                        </div>
                                        <i class="fas fa-arrow-down text-danger opacity-50 fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center bg-light p-3 rounded border">
                                <div class="mb-2 mb-sm-0">
                                    <span class="text-muted d-block small text-uppercase fw-bold mb-1">Saldo Esperado en Caja</span>
                                    <h3 class="mb-0 text-dark fw-bold">S/ {{ number_format($expected, 2) }}</h3>
                                </div>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-detail-{{ $cb->id }}">
                                    <i class="fas fa-search me-2"></i>Ver Detalle de Movimientos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    // Preparar lista combinada para la tabla modal
                    $display_list = $movs->map(function($m) {
                        return (object)[
                            'date' => $m->date,
                            'type_label' => $m->type == 'paid' ? 'Venta' : ($m->type == 'income' ? 'Ingreso Manual' : 'Deuda'),
                            'type_class' => $m->type == 'paid' ? 'bg-green-lt' : ($m->type == 'income' ? 'bg-blue-lt' : 'bg-yellow-lt'),
                            'method' => optional($m->payment_method)->name ?? 'Sin método',
                            'amount' => $m->amount,
                            'amount_color' => 'text-primary',
                            'user' => $m->user->name ?? 'Sistema',
                            'note' => $m->note
                        ];
                    });

                    $expense_display = $cb->expenses_list->map(function($e) {
                        return (object)[
                            'date' => $e->date,
                            'type_label' => 'Egreso / Gasto',
                            'type_class' => 'bg-red-lt',
                            'method' => optional($e->payment_method)->name ?? 'Sin método',
                            'amount' => $e->amount,
                            'amount_color' => 'text-danger',
                            'user' => 'N/A',
                            'note' => $e->description
                        ];
                    });

                    $combined = $display_list->concat($expense_display)->sortByDesc('date');
                @endphp
                
                @if($cb->note)
                <div class="card-footer bg-white border-top-0 p-3">
                    <div class="alert alert-warning mb-0 p-2 small">
                        <i class="fas fa-sticky-note me-2"></i><strong>Nota de Cierre:</strong> {{ $cb->note }}
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Modal Detalle -->
        <div class="modal modal-blur fade" id="modal-detail-{{ $cb->id }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="ti ti-list me-2"></i>Detalle de Movimientos - Caja #{{ $cb->id }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        @if($combined->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-vcenter table-hover mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th class="ps-4 text-muted text-uppercase small">Hora</th>
                                        <th class="text-muted text-uppercase small">Tipo</th>
                                        <th class="text-muted text-uppercase small">Método</th>
                                        <th class="text-muted text-uppercase small">Monto</th>
                                        <th class="text-muted text-uppercase small">Usuario</th>
                                        <th class="pe-4 text-muted text-uppercase small">Nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($combined as $movement)
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $movement->date ? \Carbon\Carbon::parse($movement->date)->format('d/m/Y H:i') : 'N/A' }}</td>
                                        <td>
                                            <span class="badge {{ $movement->type_class }}">{{ $movement->type_label }}</span>
                                        </td>
                                        <td>{{ $movement->method }}</td>
                                        <td class="fw-bold {{ $movement->amount_color }}">S/{{ number_format($movement->amount, 2) }}</td>
                                        <td>{{ $movement->user }}</td>
                                        <td class="pe-4 text-muted small">{{ $movement->note }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <i class="ti ti-receipt text-muted mb-2" style="font-size: 3rem;"></i>
                            <h5 class="text-muted">No hay movimientos registrados</h5>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@else
    <div class="alert alert-info border-0 shadow-sm text-center py-5">
        <i class="fas fa-info-circle fa-3x mb-3 text-info"></i>
        <h4>No hay movimientos registrados para esta fecha</h4>
        <p class="text-muted">Selecciona otro día en el filtro superior.</p>
    </div>
@endif

<style>
    .transition-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.1)!important;
    }
    .transition-hover {
        transition: all .3s ease;
    }
</style>
@endsection
