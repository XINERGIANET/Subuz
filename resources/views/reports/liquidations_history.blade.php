@extends('template.app')

@section('title', 'Historial de Liquidaciones')

@section('content')
<nav class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Inicio</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reportes</a></li>
    <li class="breadcrumb-item"><a href="{{ route('reports.liquidation') }}">Liquidación</a></li>
    <li class="breadcrumb-item active">Historial</li>
  </ol>
</nav>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">Historial de Liquidaciones Generadas</h4>
        <a href="{{ route('reports.liquidation') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left icon"></i> Volver a generar
        </a>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>Fecha de Registro</th>
                    <th>Cliente</th>
                    <th>Rango de Fechas</th>
                    <th>Fecha de Pago</th>
                    <th>Comprobante</th>
                    <th>Total</th>
                    <!-- <th>Acción</th> -->
                </tr>
            </thead>
            <tbody>
                @forelse($liquidations as $liquidation)
                <tr>
                    <td>{{ $liquidation->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ optional($liquidation->client)->name }}</td>
                    <td>{{ $liquidation->start_date->format('d/m/Y') }} - {{ $liquidation->end_date->format('d/m/Y') }}</td>
                    <td>{{ $liquidation->payment_date ? $liquidation->payment_date->format('d/m/Y') : 'N/A' }}</td>
                    <td>
                        @if($liquidation->correlative_type == 'general')
                            <span class="badge bg-primary-lt">General: {{ $liquidation->general_correlative }}</span>
                        @elseif($liquidation->correlative_type == 'per_sale')
                            <span class="badge bg-azure-lt">Por venta ({{ is_array($liquidation->sale_correlatives) ? count($liquidation->sale_correlatives) : 0 }} regs)</span>
                        @else
                            <span class="badge bg-secondary-lt">Ninguno</span>
                        @endif
                    </td>
                    <td class="fw-bold">S/{{ number_format($liquidation->total, 2) }}</td>
                    <!-- <td>
                        <a href="#" class="btn btn-icon btn-outline-danger" title="Ver PDF">
                            <i class="ti ti-file-type-pdf icon"></i>
                        </a>
                    </td> -->
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No hay historial de liquidaciones registrado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($liquidations->hasPages())
    <div class="card-footer d-flex align-items-center">
        {{ $liquidations->links() }}
    </div>
    @endif
</div>
@endsection
