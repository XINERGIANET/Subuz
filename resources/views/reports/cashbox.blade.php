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
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4" style="color: #465fff;">Monto Apertura</th>
                                    <th style="color: #465fff;">Monto Cierre</th>
                                    <th style="color: #465fff;">Ingresos (Ventas)</th>
                                    <th style="color: #465fff;">Ingresos (Manual)</th>
                                    <th style="color: #465fff;">Egresos</th>
                                    <th class="pe-4 text-end" style="color: #465fff;">Saldo Esperado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $movs = $cb->movements;
                                    $total_paid = $movs->where('type', 'paid')->sum('amount');
                                    $total_income = $movs->where('type', 'income')->sum('amount');
                                    
                                    // Los egresos no están en movements?
                                    // Según CashboxController, se calculan por fecha de Expense.
                                    // Vamos a replicar esa lógica.
                                    $start = $cb->opened_at;
                                    $end = $cb->is_open ? now() : $cb->closed_at;
                                    
                                    $total_expenses = \App\Models\Expense::whereBetween('date', [$start, $end])->sum('amount');
                                    $expected = ($cb->opening_amount + $total_paid + $total_income) - $total_expenses;
                                @endphp
                                <tr>
                                    <td class="ps-4 fw-bold">S/{{ number_format($cb->opening_amount, 2) }}</td>
                                    <td class="fw-bold">S/{{ number_format($cb->closing_amount, 2) }}</td>
                                    <td class="text-success small">+ S/{{ number_format($total_paid, 2) }}</td>
                                    <td class="text-info small">+ S/{{ number_format($total_income, 2) }}</td>
                                    <td class="text-danger small">- S/{{ number_format($total_expenses, 2) }}</td>
                                    <td class="pe-4 text-end fw-bold">S/{{ number_format($expected, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    @if($movs->count() > 0)
                    <div class="p-0 border-top">
                        <div class="bg-light p-3 border-bottom">
                            <h6 class="fw-bold mb-0 small text-uppercase"><i class="ti ti-list me-2"></i>Detalle de Movimientos</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Hora</th>
                                        <th>Tipo</th>
                                        <th>Método</th>
                                        <th>Monto</th>
                                        <th>Usuario</th>
                                        <th class="pe-4">Nota</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($movs as $movement)
                                    <tr>
                                        <td class="ps-4 text-muted">{{ $movement->date ? $movement->date->format('d/m/Y H:i') : 'N/A' }}</td>
                                        <td>
                                            @if($movement->type == 'paid')
                                                <span class="badge bg-green-lt">Venta</span>
                                            @elseif($movement->type == 'income')
                                                <span class="badge bg-blue-lt">Ingreso Manual</span>
                                            @elseif($movement->type == 'debt')
                                                <span class="badge bg-yellow-lt">Deuda</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($movement->payment_method)->name ?? 'Sin método' }}</td>
                                        <td class="fw-bold text-primary">S/{{ number_format($movement->amount, 2) }}</td>
                                        <td>{{ $movement->user->name ?? 'Sistema' }}</td>
                                        <td class="pe-4 text-muted small">{{ $movement->note }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
                @if($cb->note)
                <div class="card-footer bg-white border-top-0 p-3">
                    <div class="alert alert-warning mb-0 p-2 small">
                        <i class="fas fa-sticky-note me-2"></i><strong>Nota de Cierre:</strong> {{ $cb->note }}
                    </div>
                </div>
                @endif
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
